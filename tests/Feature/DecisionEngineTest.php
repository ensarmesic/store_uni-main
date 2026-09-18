<?php

namespace Tests\Feature;

use App\Models\{FitPassportEntry, Offer, OfferVariantAvailabilityHistory, Product, Store, VariantPriceHistory};
use App\Services\{FitRecommendation, ProductPriceInsights, PurchaseDecision, StockPressure};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecisionEngineTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug = 'shoe', string $brand = 'NIKE'): Product
    {
        return Product::create(['slug' => $slug, 'name' => $slug, 'model' => $slug, 'brand' => $brand]);
    }

    private function offer(Product $product, string $storeSlug, float $price = 100): Offer
    {
        $store = Store::firstOrCreate(['slug' => $storeSlug], ['name' => $storeSlug, 'website_url' => 'https://example.com']);
        $offer = Offer::create(['product_id' => $product->id, 'store_id' => $store->id, 'external_id' => uniqid(),
            'name_original' => $product->name, 'price' => 250, 'currency' => 'BAM', 'is_active' => true,
            'availability' => 'in_stock', 'last_checked_at' => now(), 'sizes_checked_at' => now(), 'product_url' => 'https://example.com/shoe']);
        $offer->variants()->create(['size' => '43', 'price' => $price, 'availability' => 'in_stock']);
        return $offer;
    }

    public function test_size_history_is_not_mixed_with_another_size_and_needs_real_observations(): void
    {
        $product = $this->product(); $offer = $this->offer($product, 'one');
        $variant = $offer->variants()->first();
        $other = $offer->variants()->create(['size' => '44', 'price' => 50, 'availability' => 'in_stock']);
        foreach ([10, 5, 0] as $days) {
            VariantPriceHistory::create(['offer_variant_id' => $variant->id, 'price' => 100, 'observed_on' => now()->subDays($days)->toDateString(), 'recorded_at' => now()->subDays($days)]);
            VariantPriceHistory::create(['offer_variant_id' => $other->id, 'price' => 50, 'observed_on' => now()->subDays($days)->toDateString(), 'recorded_at' => now()->subDays($days)]);
        }
        $decision = app(PurchaseDecision::class)->for($product, '43');
        $this->assertSame('buy', $decision['status']);
        $this->assertSame(100.0, $decision['prices']['low_90']);
        $this->assertSame('unavailable', app(PurchaseDecision::class)->for($product, '49')['status']);
        $variant->update(['price' => 140]);
        $this->assertSame('wait', app(PurchaseDecision::class)->for($product->fresh(), '43')['status']);
        $offer->update(['last_checked_at' => now(), 'sizes_checked_at' => now()->subDays(4)]);
        $this->assertSame('unavailable', app(PurchaseDecision::class)->for($product->fresh(), '43')['status']);
    }

    public function test_stock_pressure_uses_distinct_comparable_stores_and_excludes_missing_offers(): void
    {
        $product = $this->product();
        foreach (['one', 'one', 'two', 'missing', 'new', 'stale'] as $store) {
            $offer = $this->offer($product, $store); $variant = $offer->variants()->first();
            if ($store !== 'new') OfferVariantAvailabilityHistory::create(['offer_variant_id' => $variant->id, 'availability' => 'in_stock', 'recorded_at' => now()->subDays(15)]);
            if ($store === 'two') {
                $variant->update(['availability' => 'out_of_stock']);
                OfferVariantAvailabilityHistory::create(['offer_variant_id' => $variant->id, 'availability' => 'out_of_stock', 'recorded_at' => now()->subDay()]);
            }
            if ($store === 'missing') $offer->update(['is_active' => false]);
            if ($store === 'stale') $offer->update(['last_checked_at' => now(), 'sizes_checked_at' => now()->subDays(5)]);
        }
        $stock = app(StockPressure::class)->for($product, '43');
        $this->assertSame(50, $stock['score']);
        $this->assertSame(2, $stock['periods'][7]['before']);
        $this->assertSame(1, $stock['periods'][7]['now']);
        $this->assertSame(2, $stock['current_stores']);
        $this->assertSame(1, $stock['stale_stores']);
    }

    public function test_sparse_data_does_not_generate_buy_advice_or_fake_zero_minimum(): void
    {
        $product = $this->product(); $this->offer($product, 'one');
        $this->assertSame('insufficient', app(PurchaseDecision::class)->for($product, '43')['status']);
        $this->get(route('product.show', ['slug' => $product->slug, 'size' => '43']))
            ->assertOk()->assertSee('Još nema dovoljno podataka za odluku')->assertDontSee('minimuma: 0,00 KM');
    }

    public function test_fit_graph_transfers_relative_delta_and_does_not_use_population_size(): void
    {
        $source = $this->product('source'); $target = $this->product('target', 'ADIDAS');
        FitPassportEntry::create(['session_id' => 'me', 'product_id' => $source->id, 'size' => '46', 'fit' => 'just_right']);
        foreach (['a', 'b', 'c'] as $session) {
            FitPassportEntry::create(['session_id' => $session, 'product_id' => $source->id, 'size' => '42', 'fit' => 'just_right']);
            FitPassportEntry::create(['session_id' => $session, 'product_id' => $target->id, 'size' => '42 1/3', 'fit' => 'just_right']);
        }
        $fit = app(FitRecommendation::class)->for($target, 'me');
        $this->assertSame('46 1/3', $fit['size']);
        $this->assertSame(3, $fit['samples']);
        $this->assertSame('model_graph', $fit['source']);
        FitPassportEntry::where('session_id', 'c')->where('product_id', $target->id)->delete();
        $this->assertNull(app(FitRecommendation::class)->for($target, 'me'));
    }

    public function test_tight_feedback_is_not_recommended_as_a_perfect_fit(): void
    {
        $product = $this->product();
        FitPassportEntry::create(['session_id' => 'me', 'product_id' => $product->id, 'size' => '43', 'fit' => 'tight']);
        $fit = app(FitRecommendation::class)->for($product, 'me');
        $this->assertFalse($fit['suitable']);
        $this->assertSame(0, $fit['confidence']);
        $this->assertSame('check_fit', app(PurchaseDecision::class)->for($product, '43', $fit)['status']);
    }
}

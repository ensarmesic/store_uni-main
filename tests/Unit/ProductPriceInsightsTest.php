<?php

namespace Tests\Unit;

use App\Models\{Offer, PriceHistory, Product, Store};
use App\Services\ProductPriceInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_market_price_metrics_from_product_history(): void
    {
        $store = Store::create(['name' => 'Test Store', 'slug' => 'test-store', 'website_url' => 'https://www.sportvision.ba']);
        $product = Product::create(['brand' => 'Nike', 'name' => 'Nike Test', 'model' => 'Test', 'slug' => 'nike-test']);
        $offer = Offer::create([
            'product_id' => $product->id, 'store_id' => $store->id, 'external_id' => 'test-1',
            'name_original' => 'Nike Test patike', 'price' => 180, 'currency' => 'BAM',
            'availability' => 'in_stock', 'product_url' => 'https://www.sportvision.ba/patike/test',
            'last_checked_at' => now(), 'last_seen_at' => now(), 'is_active' => true,
        ]);

        PriceHistory::create(['offer_id' => $offer->id, 'price' => 220, 'recorded_at' => now()->subDays(120)]);
        PriceHistory::create(['offer_id' => $offer->id, 'price' => 200, 'recorded_at' => now()->subDays(60)]);
        PriceHistory::create(['offer_id' => $offer->id, 'price' => 180, 'recorded_at' => now()]);

        $insights = app(ProductPriceInsights::class)->for($product->fresh(['offers.variants']));

        $this->assertSame(180.0, $insights['current']);
        $this->assertSame(180.0, $insights['low_90']);
        $this->assertSame(190.0, $insights['average_90']);
        $this->assertSame(180.0, $insights['low_180']);
        $this->assertSame(33.0, $insights['percentile']);
        $this->assertGreaterThan(50, $insights['deal_score']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\{Offer, Product, Store};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedComparisonTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $slug, float $price): Product
    {
        $product = Product::create(['slug' => $slug, 'name' => $slug, 'brand' => 'NIKE']);
        $store = Store::firstOrCreate(['slug' => 'test'], ['name' => 'Test', 'website_url' => 'https://example.com']);
        $offer = Offer::create([
            'product_id' => $product->id, 'store_id' => $store->id, 'external_id' => $slug,
            'name_original' => $slug, 'price' => 300, 'currency' => 'BAM',
            'availability' => 'in_stock', 'is_active' => true, 'product_url' => 'https://example.com/'.$slug, 'last_checked_at' => now(),
        ]);
        $offer->variants()->create(['size' => '43', 'price' => $price, 'availability' => 'in_stock']);
        return $product;
    }

    public function test_shared_link_preserves_order_size_and_visitors_own_comparison(): void
    {
        $one = $this->product('one', 120);
        $two = $this->product('two', 170);
        $this->withSession(['comparison' => [$one->id], 'shopping_preferences' => ['size' => '49']])
            ->get(route('compare', ['models' => [$two->id, $one->id], 'size' => '43']))
            ->assertOk()->assertSee('120,00 KM')->assertSee('+50,00 KM')
            ->assertSee('Najpovoljniji izbor')->assertDontSee('Ukloni iz poređenja')
            ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$two->id, $one->id])
            ->assertViewHas('comparisonQuery', ['models' => [$two->id, $one->id]])
            ->assertSessionHas('comparison', [$one->id]);
    }

    public function test_shared_comparison_without_size_ignores_personal_preferences(): void
    {
        $product = $this->product('one', 120);
        $this->withSession(['shopping_preferences' => ['size' => '49']])
            ->get(route('compare', ['models' => [$product->id]]))
            ->assertOk()->assertViewHas('size', null)->assertSee('300,00 KM');
    }

    public function test_share_url_round_trips_models_and_size_without_session(): void
    {
        $product = $this->product('one', 120);
        $response = $this->withSession(['comparison' => [$product->id]])->get('/compare?size=43')->assertOk();
        $url = $response->viewData('shareUrl');
        $this->flushSession();
        $this->get($url)->assertOk()->assertSee('120,00 KM')->assertViewHas('shared', true);
    }

    public function test_invalid_shared_models_are_rejected(): void
    {
        foreach (['bad', [], [1, 2, 3, 4], [1, 1], ['bad'], [0], [[1]]] as $models) {
            $this->getJson(route('compare', ['models' => $models === [] ? '' : $models]))->assertUnprocessable();
        }
    }

    public function test_deleted_models_and_unavailable_sizes_are_handled(): void
    {
        $product = $this->product('one', 120);
        $this->get(route('compare', ['models' => [$product->id, $product->id + 1], 'size' => '49']))
            ->assertOk()->assertViewHas('missingModels', 1)->assertSee('Nedostupno')
            ->assertDontSee('Najpovoljniji izbor')->assertDontSee('120,00 KM');
        $this->get(route('compare', ['models' => [$product->id + 1]]))
            ->assertOk()->assertViewHas('missingModels', 1);
    }

    public function test_equal_prices_mark_both_models_as_cheapest(): void
    {
        $one = $this->product('one', 120);
        $two = $this->product('two', 120);
        $response = $this->get(route('compare', ['models' => [$one->id, $two->id], 'size' => '43']))->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'Najpovoljniji izbor'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\{Offer, Product, Store};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogRankingTest extends TestCase
{
    use RefreshDatabase;

    private function offer(Product $product, Store $store, float $price, float $oldPrice, bool $active = true): Offer
    {
        return Offer::create([
            'product_id' => $product->id, 'store_id' => $store->id,
            'external_id' => (string) Offer::count(), 'name_original' => $product->name,
            'price' => $price, 'old_price' => $oldPrice, 'currency' => 'BAM',
            'availability' => 'in_stock', 'is_active' => $active,
            'product_url' => 'https://example.com/shoe', 'last_checked_at' => now(),
        ]);
    }

    private function product(string $name): Product
    {
        return Product::create(['brand' => 'NIKE', 'name' => $name, 'model' => $name, 'slug' => $name]);
    }

    private function store(string $name): Store
    {
        return Store::create(['name' => $name, 'slug' => $name, 'website_url' => 'https://example.com']);
    }

    public function test_discount_ranking_respects_selected_size_and_active_offers(): void
    {
        $store = $this->store('store');
        $first = $this->product('first');
        $second = $this->product('second');
        $this->offer($first, $store, 100, 110)->variants()->create(['size' => '43', 'availability' => 'in_stock']);
        $this->offer($first, $store, 100, 500)->variants()->create(['size' => '42', 'availability' => 'in_stock']);
        $this->offer($first, $store, 10, 900, false)->variants()->create(['size' => '43', 'availability' => 'in_stock']);
        $this->offer($second, $store, 100, 150)->variants()->create(['size' => '43', 'availability' => 'in_stock']);

        $this->get('/catalog?size=43&sort=discount')->assertOk()->assertViewHas('products',
            fn ($products) => $products->pluck('id')->all() === [$second->id, $first->id]);
    }

    public function test_store_ranking_counts_distinct_stores_instead_of_offers(): void
    {
        $store = $this->store('store');
        $otherStore = $this->store('other');
        $first = $this->product('first');
        $second = $this->product('second');
        for ($i = 0; $i < 3; $i++) $this->offer($first, $store, 100, 100);
        $this->offer($second, $store, 100, 100);
        $this->offer($second, $otherStore, 110, 110);

        $this->get('/catalog?sort=stores')->assertOk()->assertViewHas('products',
            fn ($products) => $products->pluck('id')->all() === [$second->id, $first->id]);
    }

    public function test_popular_price_excludes_inactive_offers(): void
    {
        $store = $this->store('store');
        $product = $this->product('shoe');
        $this->offer($product, $store, 150, 150);
        $this->offer($product, $store, 20, 150, false);

        $this->get('/catalog')->assertOk()->assertViewHas('popularProducts',
            fn ($products) => (float) $products->first()->display_min_price === 150.0);
    }

    public function test_removing_natural_search_filter_does_not_restore_it_from_query(): void
    {
        $this->get('/catalog?q=Nike+43+do+180+KM')->assertOk()
            ->assertSee('aria-label="Aktivni filteri"', false)
            ->assertSee(route('catalog', ['size' => '43', 'price_max' => 180]).'#rezultati')
            ->assertSee('href="'.route('scanner').'"', false);
    }
}

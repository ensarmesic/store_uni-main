<?php

namespace Tests\Feature;

use App\Models\{Offer, Product, Store};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_multibrand_catalog_and_product_detail_render(): void
    {
        $store = Store::create(['name' => 'Sport Reality', 'slug' => 'sportreality', 'website_url' => 'https://www.sportreality.ba']);
        $product = Product::create(['brand' => 'NIKE', 'name' => 'NIKE AIR MAX 90', 'model' => 'AIR MAX 90', 'gender' => 'male', 'slug' => 'nike-air-max-90']);
        $offer = Offer::create(['product_id' => $product->id, 'store_id' => $store->id, 'external_id' => 'nike-90', 'name_original' => 'Nike Air Max 90', 'price' => 249.90, 'old_price' => 299.90, 'currency' => 'BAM', 'availability' => 'in_stock', 'product_url' => 'https://www.sportreality.ba/patike/nike-air-max-90', 'last_checked_at' => now()]);
        $offer->variants()->create(['size' => '44', 'availability' => 'in_stock']);

        $this->get('/catalog')->assertOk()->assertSee('NE PREPLAĆUJ')->assertSee('NIKE')->assertSee('Sport Reality');
        $this->get('/product/nike-air-max-90')->assertOk()->assertSee('AIR MAX 90')->assertSee('PONUDE BIH TRGOVINA');
        $this->get('/analytics')->assertOk()->assertSee('CIJENE BEZ')->assertSee('VELIKA')->assertSee('Sport Reality');
        $this->get('/deals')->assertOk()->assertSee('DEAL RADAR')->assertSee('AIR MAX 90')->assertSee('Sport Reality');
        $this->get('/deals?brand=NIKE&min_discount=10&size=44')->assertOk()->assertSee('AIR MAX 90');
        $this->get('/deals?brand=ADIDAS')->assertOk()->assertDontSee('AIR MAX 90');
    }
}

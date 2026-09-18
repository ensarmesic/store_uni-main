<?php

namespace Tests\Feature;

use App\Models\{Offer, OfferVariant, Product, ProductAlert, Store};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_alert_for_a_size_is_triggered_by_the_checker(): void
    {
        $store = Store::create(['name' => 'Test Store', 'slug' => 'test-store', 'website_url' => 'https://www.sportvision.ba']);
        $product = Product::create(['brand' => 'Nike', 'name' => 'Nike Test', 'model' => 'Test', 'slug' => 'nike-alert-test']);
        $offer = Offer::create([
            'product_id' => $product->id, 'store_id' => $store->id, 'external_id' => 'alert-1',
            'name_original' => 'Nike Test patike', 'price' => 200.00, 'currency' => 'BAM',
            'availability' => 'in_stock', 'product_url' => 'https://www.sportvision.ba/patike/alert-1',
            'last_checked_at' => now(), 'last_seen_at' => now(), 'is_active' => true,
        ]);
        OfferVariant::create(['offer_id' => $offer->id, 'size' => '43', 'availability' => 'in_stock', 'price' => 159.90]);

        $this->post(route('alerts.store', $product), ['type' => 'price', 'size' => '43', 'target_price' => 160])->assertRedirect();
        $this->assertDatabaseHas('product_alerts', ['type' => 'price', 'size' => '43', 'is_active' => 1]);

        Artisan::call('alerts:check');

        $this->assertDatabaseHas('product_alerts', ['type' => 'price', 'size' => '43', 'is_active' => 0]);
    }

}

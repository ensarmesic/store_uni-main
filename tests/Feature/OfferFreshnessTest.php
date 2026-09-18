<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\OfferVariantAvailabilityHistory;
use App\Services\ProductImport\{ImportManager, ProductMatcher};
use App\Services\ProductImport\Contracts\StoreImporterInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferFreshnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_sync_marks_unseen_offers_inactive_and_tracks_size_changes(): void
    {
        $importer = new class implements StoreImporterInterface {
            public array $products = [];

            public function getStoreName(): string { return 'Sport Vision'; }
            public function getStoreSlug(): string { return 'sportvision'; }
            public function getWebsiteUrl(): string { return 'https://www.sportvision.ba'; }
            public function fetchProducts(): iterable { yield from $this->products; }
            public function normalize(array $product): array { return $product; }
        };

        $importer->products = [[
            'store_product_id' => 'sku-1', 'brand' => 'Nike', 'model' => 'Air Max Test',
            'name_original' => 'Nike Air Max Test patike', 'name' => 'Nike Air Max Test patike',
            'current_price' => 199.90, 'product_url' => 'https://www.sportvision.ba/patike/sku-1',
            'available_sizes' => ['43'], 'availability' => 'in_stock', 'allow_name_match' => false,
        ]];

        $manager = new ImportManager(new ProductMatcher);
        $manager->import($importer);

        $offer = Offer::firstOrFail();
        $this->assertTrue($offer->is_active);
        $this->assertSame(1, OfferVariantAvailabilityHistory::where('offer_variant_id', $offer->variants()->first()->id)->count());
        $this->assertDatabaseHas('variant_price_histories', ['offer_variant_id' => $offer->variants()->first()->id, 'price' => 199.90]);
        $this->assertNotNull($offer->sizes_checked_at);
        $importer->products[0]['variant_prices'] = ['43' => 179.90];
        $manager->import($importer);
        $this->assertDatabaseCount('variant_price_histories', 1);
        $this->assertDatabaseHas('variant_price_histories', ['price' => 179.90]);
        $this->travel(1)->days();
        $manager->import($importer);
        $this->assertDatabaseCount('variant_price_histories', 2);
        $this->travelBack();

        $importer->products = [[
            'store_product_id' => 'sku-1', 'brand' => 'Nike', 'model' => 'Air Max Test',
            'name_original' => 'Nike Air Max Test patike', 'name' => 'Nike Air Max Test patike',
            'current_price' => 189.90, 'product_url' => 'https://www.sportvision.ba/patike/sku-1',
            'available_sizes' => [], 'availability' => 'out_of_stock', 'allow_name_match' => false,
        ]];
        $manager->import($importer);

        $offer->refresh();
        $this->assertTrue($offer->is_active);
        $this->assertSame('out_of_stock', $offer->variants()->first()->availability);
        $this->assertSame(2, OfferVariantAvailabilityHistory::where('offer_variant_id', $offer->variants()->first()->id)->count());

        $importer->products = [];
        $manager->import($importer);

        $offer->refresh();
        $this->assertFalse($offer->is_active);
        $this->assertNotNull($offer->missing_since);
    }
}

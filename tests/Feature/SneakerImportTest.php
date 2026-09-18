<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Services\ProductImport\Importers\BuzzImporter;
use App\Services\ProductImport\Importers\NSportImporter;
use App\Services\ProductImport\ImportManager;
use App\Services\ProductImport\ProductNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Tests\TestCase;

class SneakerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_follows_pages_once_and_rejects_other_footwear(): void
    {
        Http::preventStrayRequests();
        $product = fn ($name, $sku) => '<script type="application/ld+json">'.json_encode([
            '@type' => 'Product', 'name' => $name, 'sku' => $sku,
            'offers' => ['price' => 100, 'priceCurrency' => 'BAM'],
        ]).'</script>';
        Http::fake([
            'www.buzzsneakers.ba/patike/page-1' => Http::response('<a href="/patike/page-1#tab1">Tab</a><a href="/patike/page-2">Next</a><a href="/patike/1-air">Patike</a>'),
            'www.buzzsneakers.ba/patike/page-2' => Http::response('<a href="/patike/page-1">Previous</a><a href="/patike/2-slides">Slides</a>'),
            'www.buzzsneakers.ba/patike/1-air' => Http::response($product('Nike Patike Air Max', 'AIR1')),
            'www.buzzsneakers.ba/patike/2-slides' => Http::response($product('Nike Papuče', 'SLIDE1')),
        ]);
        $run = app(ImportManager::class)->import(app(BuzzImporter::class));
        $this->assertSame(1, Offer::count());
        $this->assertSame('Patike', Offer::first()->product->category);
        $this->assertEmpty($run->errors);
        Http::assertSentCount(4);
    }

    public function test_failed_catalog_is_recorded_instead_of_reported_as_success(): void
    {
        Http::fake(['*' => Http::response('Blocked', 403)]);
        $run = app(ImportManager::class)->import(app(BuzzImporter::class));
        $this->assertNotEmpty($run->errors);
        $this->assertDatabaseHas('stores', ['slug' => 'buzz', 'last_synced_at' => null]);
        $this->assertSame(0, Offer::count());
    }

    public function test_nsport_numbered_category_pagination_is_discovered(): void
    {
        $importer = app(NSportImporter::class);
        $url = 'https://www.nsport.ba/lifestyle-patike';
        $html = '<div class="paginationTG"><a href="/lifestyle-patike/2?mod=catalog">2</a><a href="https://example.com/lifestyle-patike/3">Foreign</a></div>';
        $urls = (new \ReflectionMethod($importer, 'paginationUrls'))->invoke($importer, new Crawler($html, $url), $url, $html);
        $this->assertSame(['https://www.nsport.ba/lifestyle-patike/2?mod=catalog'], $urls);
    }

    public function test_refresh_with_better_manufacturer_code_reuses_existing_product(): void
    {
        $importer = new class(new ProductNormalizer) extends BuzzImporter
        {
            public string $code = 'store-sku';

            public function fetchProducts(): iterable
            {
                yield ['store_product_id' => '1', 'name' => 'Nike Patike Air Max', 'brand' => 'Nike',
                    'sku' => 'store-sku', 'mpn' => $this->code, 'current_price' => 100,
                    'product_url' => 'https://www.buzzsneakers.ba/patike/1-air'];
            }
        };
        app(ImportManager::class)->import($importer);
        $id = Offer::first()->product_id;
        $importer->code = 'NIKE-001';
        $run = app(ImportManager::class)->import($importer);
        $this->assertSame($id, Offer::first()->product_id);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('price_histories', 1);
        $this->assertSame(1, $run->offers_updated);
        $this->assertSame('NIKE-001', Offer::first()->product->mpn);
    }

    public function test_generic_deichmann_names_do_not_merge_unrelated_articles(): void
    {
        $importer = new class(new \App\Services\ProductImport\ProductNormalizer) extends \App\Services\ProductImport\Importers\DeichmannImporter {
            public function fetchProducts(): iterable
            {
                foreach ([1, 2] as $id) {
                    yield ['store_product_id'=>(string) $id, 'name'=>'Patike', 'brand'=>'Fila',
                        'color'=>'Crna', 'current_price'=>100,
                        'product_url'=>'https://www.deichmann.com/bs-ba/p/fila-patike-crna-'.$id];
                }
            }
        };
        app(ImportManager::class)->import($importer);
        app(ImportManager::class)->import($importer);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('offers', 2);
        $this->assertSame('PATIKE', Offer::first()->product->model);
    }

    public function test_literal_newlines_in_merchant_json_descriptions_do_not_drop_sneakers(): void
    {
        $html = '<script type="application/ld+json">{"@type":"Product","name":"Rieker Ženska patika","description":"First line'."\n".'Second line","offers":{"price":"179.00","priceCurrency":"BAM"}}</script>';
        $importer = app(BuzzImporter::class);
        $product = (new \ReflectionMethod($importer, 'parseProduct'))->invoke($importer, $html, 'https://www.buzzsneakers.ba/patike/1-rieker');
        $this->assertSame(179.0, $product['current_price']);
        $this->assertSame("First line\nSecond line",$product['description']);
    }
}

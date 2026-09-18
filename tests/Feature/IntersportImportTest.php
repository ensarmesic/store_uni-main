<?php

namespace Tests\Feature;

use App\Services\ProductImport\Importers\IntersportImporter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntersportImportTest extends TestCase
{
    public function test_catalog_pages_and_color_variants_use_product_detail_stock(): void
    {
        Http::preventStrayRequests();
        $product = [
            'id' => 1, 'sku' => 'store-sku', 'name' => 'Nike Patike Air Max', 'urlKey' => 'nike-air-max',
            'brand' => [['name' => 'Nike']], 'catalogNumber' => ['NIKE-001'],
            'images' => ['/a/air.jpg'], 'colors' => [['name' => 'Crna']],
            'sizes' => [
                ['label' => '42', 'availableForSale' => false, 'price' => ['price' => 120, 'specialPrice' => [60]]],
                ['label' => '43', 'availableForSale' => true, 'price' => ['price' => 120, 'specialPrice' => [90]]],
            ],
        ];
        Http::fake(function ($request) use ($product) {
            if (str_ends_with($request->url(), '/fetchProduct')) {
                $key = json_decode($request['urlKey'], true);

                return Http::response(['$type' => 'ProductPageResponse.Success', 'product' => [
                    ...$product, 'id' => $key === 'nike-air-max' ? 1 : 3, 'urlKey' => $key,
                ]]);
            }
            $path = json_decode($request['path'], true);
            $second = str_contains($path, '?p=2');
            $male = str_starts_with($path, 'muskarci/');

            return Http::response([
                '$type' => 'CatalogPageResponse.Success', 'numberOfMatchingItems' => $male ? 2 : 0,
                'config' => ['pageSize' => 1, 'pageNumber' => $second ? 2 : 1],
                'products' => $male ? [$second ? ['id' => 2, 'name' => 'Nike Papuče', 'urlKey' => 'slides'] : [
                    ...$product,
                    'sizes' => [],
                    'productVariants' => [['id' => 3, 'name' => 'Nike Patike Air Max Bijele', 'urlKey' => 'nike-air-max-white']],
                ]] : [],
            ]);
        });
        $importer = app(IntersportImporter::class);
        $products = iterator_to_array($importer->fetchProducts());
        $this->assertCount(2, $products);
        $this->assertSame(['43'], $products[0]['available_sizes']);
        $this->assertSame(90.0, $products[0]['current_price']);
        $this->assertSame(120.0, $products[0]['old_price']);
        $this->assertSame('NIKE-001', $products[0]['mpn']);
        $this->assertSame('in_stock', $products[0]['availability']);
        $this->assertEmpty($importer->failures());
        Http::assertSentCount(6);
    }
}

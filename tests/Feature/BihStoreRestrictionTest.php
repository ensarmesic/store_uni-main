<?php

namespace Tests\Feature;

use App\Services\ProductImport\Contracts\StoreImporterInterface;
use App\Services\ProductImport\{ImportManager, ProductMatcher};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BihStoreRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_outside_approved_bih_sources_is_rejected(): void
    {
        $foreignImporter = new class implements StoreImporterInterface {
            public function getStoreName(): string { return 'Foreign Store'; }
            public function getStoreSlug(): string { return 'foreign'; }
            public function getWebsiteUrl(): string { return 'https://example.rs'; }
            public function fetchProducts(): iterable { return []; }
            public function normalize(array $product): array { return $product; }
        };

        $this->expectException(HttpException::class);
        (new ImportManager(new ProductMatcher))->import($foreignImporter);
    }
}

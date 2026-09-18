<?php

namespace App\Services\ProductImport\Importers;

class UnderArmourImporter extends StructuredProductImporter
{
    public function getStoreName(): string
    {
        return 'Under Armour BiH';
    }

    public function getStoreSlug(): string
    {
        return 'underarmour';
    }

    public function getWebsiteUrl(): string
    {
        return 'https://www.underarmour.ba';
    }

    protected function listingUrls(): array
    {
        return ['https://www.underarmour.ba/patike'];
    }

    protected function parseProduct(string $html, string $url): ?array
    {
        $product = parent::parseProduct($html, $url);

        if ($product) {
            $product['brand'] = 'UNDER ARMOUR';
        }

        return $product;
    }
}

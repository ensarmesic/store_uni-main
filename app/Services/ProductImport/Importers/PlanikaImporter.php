<?php

namespace App\Services\ProductImport\Importers;

class PlanikaImporter extends StructuredProductImporter
{
    public function getStoreName(): string
    {
        return 'Planika BiH';
    }

    public function getStoreSlug(): string
    {
        return 'planika';
    }

    public function getWebsiteUrl(): string
    {
        return 'https://planika.ba';
    }

    protected function listingUrls(): array
    {
        return ['https://planika.ba/bs/shop/muskarci/patike', 'https://planika.ba/bs/shop/zene/patike', 'https://planika.ba/bs/shop/djeca/patike'];
    }
}

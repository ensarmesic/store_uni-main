<?php

namespace App\Services\ProductImport\Importers;

class DeichmannImporter extends StructuredProductImporter
{
    public function getStoreName(): string
    {
        return 'Deichmann BiH';
    }

    public function getStoreSlug(): string
    {
        return 'deichmann';
    }

    public function getWebsiteUrl(): string
    {
        return 'https://www.deichmann.com';
    }

    public function normalize(array $product): array
    {
        $data = parent::normalize($product);
        // Generic labels such as "Patike" do not identify a particular model.
        $data['allow_name_match'] = false;
        $data['model'] = $data['model'] ?: mb_strtoupper($data['name_original']);

        return $data;
    }

    protected function listingUrls(): array
    {
        return [
            'https://www.deichmann.com/bs-ba/c/muskarci/patike-270',
            'https://www.deichmann.com/bs-ba/c/zene/patike-143',
            'https://www.deichmann.com/bs-ba/c/djeca/patike-397',
        ];
    }

    protected function normalizeImageUrl(?string $url): ?string
    {
        if (! $url || ! str_contains($url, 'asset.deichmann.com/images/')) {
            return $url;
        }

        return preg_replace(
            '~asset\.deichmann\.com/images/[^/]+/~',
            'asset.deichmann.com/images/f_auto,q_75,w_900,ar_4:3,c_fill,g_auto/',
            $url,
            1
        );
    }

    protected function isProductUrl(string $url): bool
    {
        return parse_url($url, PHP_URL_HOST) === 'www.deichmann.com'
            && (bool) preg_match('~^/bs-ba/p/[^/]+-\d+$~', (string) parse_url($url, PHP_URL_PATH));
    }
}

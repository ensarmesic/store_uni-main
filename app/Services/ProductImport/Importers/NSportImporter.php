<?php

namespace App\Services\ProductImport\Importers;

use Symfony\Component\DomCrawler\Crawler;

class NSportImporter extends StructuredProductImporter
{
    public function getStoreName(): string
    {
        return 'N Sport BiH';
    }

    public function getStoreSlug(): string
    {
        return 'nsport';
    }

    public function getWebsiteUrl(): string
    {
        return 'https://www.nsport.ba';
    }

    protected function listingUrls(): array
    {
        return [
            'https://www.nsport.ba/patike',
            'https://www.nsport.ba/lifestyle-patike',
            'https://www.nsport.ba/patike-za-trcanje',
            'https://www.nsport.ba/patike-za-trening',
            'https://www.nsport.ba/patike-za-kosarku',
        ];
    }

    protected function paginationUrls(Crawler $crawler, string $listingUrl, string $html): array
    {
        $urls = [];
        $crawler->filter('.paginationTG a[href]')->each(function (Crawler $anchor) use (&$urls) {
            $url = $anchor->link()->getUri();
            if (parse_url($url, PHP_URL_HOST) === 'www.nsport.ba'
                && preg_match('~^/[^/]*patike[^/]*/\d+$~', (string) parse_url($url, PHP_URL_PATH))) {
                $urls[] = $url;
            }
        });

        return array_values(array_unique($urls));
    }

    protected function parseProduct(string $html, string $url): ?array
    {
        $crawler = new Crawler($html, $url);
        $button = $crawler->filter('[data-product-id][data-product-name][data-price]')->first();
        if (! $button->count()) {
            return null;
        }

        $name = html_entity_decode((string) $button->attr('data-product-name'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $sku = preg_match('/Šifra proizvoda:\s*([A-Z0-9-]+)/ui', $crawler->text(), $match) ? $match[1] : (string) $button->attr('data-product-id');
        $sizes = [];
        $crawler->filter('[name="fnc-product-cart-size"][data-size]')->each(function (Crawler $node) use (&$sizes) {
            $sizes[] = trim((string) $node->attr('data-size'));
        });

        $oldPrice = null;
        if ($crawler->filter('.product-old-price')->count()) {
            $value = $this->money($crawler->filter('.product-old-price')->first()->text(''));
            if ($value > (float) $button->attr('data-price')) {
                $oldPrice = $value;
            }
        }

        return [
            'store_product_id' => (string) $button->attr('data-product-id'),
            'name_original' => $name,
            'name' => $name,
            'brand' => $button->attr('data-brand'),
            'sku' => $sku,
            'mpn' => $sku,
            'category' => $button->attr('data-category-name') ?: 'Patike',
            'description' => $crawler->filter('meta[property="og:description"]')->attr('content'),
            'image_url' => $crawler->filter('meta[property="og:image"]')->attr('content'),
            'product_url' => $url,
            'current_price' => (float) $button->attr('data-price'),
            'old_price' => $oldPrice,
            'currency' => 'BAM',
            'availability' => $sizes ? 'in_stock' : 'unknown',
            'available_sizes' => $sizes,
        ];
    }

    private function money(string $value): float
    {
        $value = preg_replace('/[^0-9,.]/', '', $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }

        return (float) str_replace(',', '.', $value);
    }
}

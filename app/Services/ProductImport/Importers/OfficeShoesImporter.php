<?php

namespace App\Services\ProductImport\Importers;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class OfficeShoesImporter extends StructuredProductImporter
{
    public function getStoreName(): string
    {
        return 'Office Shoes BiH';
    }

    public function getStoreSlug(): string
    {
        return 'officeshoes';
    }

    public function getWebsiteUrl(): string
    {
        return 'https://www.officeshoes.ba';
    }

    protected function listingUrls(): array
    {
        return ['https://www.officeshoes.ba/obuca-patike/16532505/48/order_asc/?q=patike'];
    }

    protected function paginationUrls(Crawler $crawler, string $listingUrl, string $html): array
    {
        if (str_contains($listingUrl, '/true?')) {
            return [];
        }
        if (! preg_match('/var\s+total_pages\s*=\s*(\d+)/', $html, $match)) {
            return [];
        }

        $urls = [];
        for ($page = 1; $page < (int) $match[1]; $page++) {
            $urls[] = "https://www.officeshoes.ba/obuca-patike/16532505/48/order_asc/true?page={$page}";
        }

        return $urls;
    }

    protected function getListing(string $url): string
    {
        if (! str_contains($url, '/true?')) {
            return parent::getListing($url);
        }

        usleep((int) env('IMPORT_DELAY_MS', 750) * 1000);

        return Http::withHeaders([
            'User-Agent' => env('IMPORT_USER_AGENT'),
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => 'https://www.officeshoes.ba/obuca-patike/16532505/48/order_asc/?q=patike',
        ])->withOptions(['verify' => config('catalog.ca_bundle') ?: true])->timeout(20)->retry(3, fn (int $attempt) => 250 * (2 ** $attempt))->get($url)->throw()->body();
    }

    protected function parseProduct(string $html, string $url): ?array
    {
        $crawler = new Crawler($html, $url);

        try {
            $name = trim(preg_replace('/\s+/u', ' ', $crawler->filter('h1')->first()->text()));
            $price = (float) $crawler->filter('[itemprop="price"]')->first()->attr('content');
            $image = $crawler->filter('meta[property="og:image"]')->first()->attr('content');
        } catch (\Throwable) {
            return null;
        }

        if ($name === '' || $price <= 0) {
            return null;
        }

        $brand = null;
        if (preg_match('/^(.+?)\s+(?:(?:Muške|Ženske|Dječije)\s+)?(?:Plitke|Duboke|Kožne|Slip-ins|Patike)/ui', $name, $match)) {
            $brand = trim($match[1]);
        }

        $sizes = [];
        $crawler->filter('[data-product-size]')->each(function (Crawler $node) use (&$sizes) {
            $size = trim((string) $node->attr('data-product-size'));
            if ($size !== '') {
                $sizes[$size] = true;
            }
        });

        $sku = null;
        $description = [];
        $crawler->filter('.content-details li')->each(function (Crawler $node) use (&$sku, &$description) {
            $text = trim(preg_replace('/\s+/u', ' ', $node->text()));
            if (preg_match('/Šifra proizvoda:\s*(.+)$/ui', $text, $match)) {
                $sku = trim($match[1]);
            }
            if ($text !== '') {
                $description[] = $text;
            }
        });

        $externalId = basename((string) parse_url($url, PHP_URL_PATH));

        return [
            'store_product_id' => $externalId,
            'name_original' => $name,
            'name' => $name,
            'brand' => $brand,
            'sku' => $sku,
            'category' => 'Patike',
            'description' => implode(' ', array_slice($description, 0, 4)),
            'image_url' => $image,
            'product_url' => $url,
            'current_price' => $price,
            'currency' => 'BAM',
            'availability' => $sizes ? 'in_stock' : 'unknown',
            'available_sizes' => array_keys($sizes),
        ];
    }
}

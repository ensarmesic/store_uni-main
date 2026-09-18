<?php

namespace App\Services\ProductImport\Importers;

use App\Models\Offer;
use App\Services\ProductImport\SneakerFilter;
use Symfony\Component\DomCrawler\Crawler;

abstract class StructuredProductImporter extends AbstractHtmlImporter
{
    abstract protected function listingUrls(): array;

    public function fetchProducts(): iterable
    {
        $links = [];
        $listingQueue = $this->listingUrls();
        $visitedListings = [];
        $pageLimit = max(0, (int) env('IMPORT_PAGE_LIMIT', 0));

        while ($listingQueue && (! $pageLimit || count($visitedListings) < $pageLimit)) {
            $listingUrl = array_shift($listingQueue);
            if (isset($visitedListings[$listingUrl])) {
                continue;
            }
            $visitedListings[$listingUrl] = true;

            try {
                $html = $this->getListing($listingUrl);
            } catch (\Throwable $exception) {
                $this->reportFailure($listingUrl, $exception->getMessage());

                continue;
            }

            $crawler = new Crawler($html, $listingUrl);
            $crawler->filter('a[href]')->each(function (Crawler $anchor) use (&$links) {
                try {
                    $url = preg_replace('~#.*$~', '', preg_replace('~^https?://~i', 'https://', $anchor->link()->getUri()));
                    if ($this->isProductUrl($url)) {
                        $links[$url] = true;
                    }
                } catch (\Throwable) {
                    // Ignore malformed links supplied by the merchant page.
                }
            });

            foreach ($this->paginationUrls($crawler, $listingUrl, $html) as $nextUrl) {
                if (! isset($visitedListings[$nextUrl]) && ! in_array($nextUrl, $listingQueue, true)) {
                    $listingQueue[] = $nextUrl;
                }
            }
            if (PHP_SAPI === 'cli') {
                echo $this->getStoreSlug().' listing '.count($visitedListings).' / queue '.count($listingQueue).' / products '.count($links).PHP_EOL;
            }
        }

        if ($listingQueue) {
            $this->reportFailure($listingUrl, 'Listing page limit reached; import is incomplete.');
        }
        if ($this->missingOnly) {
            $existing = Offer::whereHas('store', fn ($store) => $store->where('slug', $this->getStoreSlug()))->pluck('product_url');
            foreach ($existing as $url) {
                unset($links[$url]);
            }
        }
        $limit = max(0, (int) env('IMPORT_PRODUCT_LIMIT', 0));
        if ($limit && count($links) > $limit) {
            $this->reportFailure($this->getWebsiteUrl(), 'Product limit reached; import is incomplete.');
        }
        $concurrency = max(1, min(12, (int) env('IMPORT_CONCURRENCY', 8)));
        foreach (array_chunk($limit ? array_slice(array_keys($links), 0, $limit) : array_keys($links), $concurrency) as $urls) {
            try {
                $pages = $this->getMany($urls);
            } catch (\Throwable) {
                $pages = [];
            }

            foreach ($urls as $url) {
                try {
                    $html = $pages[$url] ?? $this->get($url);
                    $product = $this->parseProduct($html, $url);
                    if (! $product) {
                        $this->reportFailure($url, 'No readable product or positive price.');
                    }
                    if ($product && $this->isSupportedProduct($product)) {
                        $product['category'] = 'Patike';
                        yield $product;
                    }
                } catch (\Throwable $exception) {
                    $this->reportFailure($url, $exception->getMessage());

                    continue;
                }
            }
        }
    }

    protected function getListing(string $url): string
    {
        return $this->get($url);
    }

    protected function paginationUrls(Crawler $crawler, string $listingUrl, string $html): array
    {
        $urls = [];
        $expectedHost = strtolower((string) parse_url($this->getWebsiteUrl(), PHP_URL_HOST));

        $crawler->filter('a[href]')->each(function (Crawler $anchor) use (&$urls, $expectedHost) {
            try {
                $url = preg_replace('~#.*$~', '', $anchor->link()->getUri());
                $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                $path = (string) parse_url($url, PHP_URL_PATH);
                $query = (string) parse_url($url, PHP_URL_QUERY);

                if ($host === $expectedHost
                    && preg_match('/patik|obuca|obuća|cipele/ui', $path)
                    && (preg_match('~/page-\d+/?$~', $path) || preg_match('/(?:^|&)page=\d+(?:&|$)/', $query))) {
                    $urls[$url] = true;
                }
            } catch (\Throwable) {
                // Ignore malformed pagination links.
            }
        });

        return array_keys($urls);
    }

    protected function isProductUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== parse_url($this->getWebsiteUrl(), PHP_URL_HOST)) {
            return false;
        }

        $path = rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/');

        return (bool) preg_match('~/(patike|obuca|cipele|kopacke)/\d+-|/proizvod/.+/\d+$|/proizvod/[^/]*-[a-z0-9]{6,}$|/p/[^/]+-\d+$|/cipele-[^/]+/\d+$|[^/]*patike[^/]*\.html$|-(?:\d{6,}|[a-z]{1,5}\d{3,})$~iu', $path);
    }

    protected function isSupportedProduct(array $product): bool
    {
        return (new SneakerFilter)->accepts($product);
    }

    protected function parseProduct(string $html, string $url): ?array
    {
        $crawler = new Crawler($html, $url);

        foreach ($crawler->filter('script[type="application/ld+json"]') as $node) {
            $json = json_decode($node->textContent, true);
            if (json_last_error() === JSON_ERROR_CTRL_CHAR) {
                // Some shops embed literal newlines in JSON string descriptions.
                $repaired = preg_replace_callback('/"(?:[^"\\\\]|\\\\.)*"/s', static function ($match) {
                    return preg_replace_callback('/[\x00-\x1F]/', static fn ($control) => sprintf('\\u%04x', ord($control[0])), $match[0]);
                }, $node->textContent);
                $json = json_decode($repaired, true);
            }
            if (! is_array($json)) {
                continue;
            }
            $items = isset($json['@graph']) ? $json['@graph'] : [$json];

            foreach ($items as $item) {
                if (($item['@type'] ?? null) !== 'Product') {
                    continue;
                }

                $offer = is_array($item['offers'] ?? null) && isset($item['offers'][0])
                    ? $item['offers'][0]
                    : ($item['offers'] ?? []);
                $sku = (string) ($item['sku'] ?? $item['mpn'] ?? sha1($url));
                $price = $this->decimal($offer['price'] ?? 0);
                if ($price <= 0) {
                    continue;
                }

                $imageUrl = is_array($item['image'] ?? null) ? ($item['image'][0] ?? null) : ($item['image'] ?? null);

                return [
                    'store_product_id' => $sku,
                    'name_original' => $item['name'] ?? '',
                    'name' => $item['name'] ?? '',
                    'brand' => $item['brand'] ?? null,
                    'sku' => $item['sku'] ?? null,
                    'mpn' => $item['mpn'] ?? null,
                    'ean' => $item['gtin13'] ?? null,
                    'gtin' => $item['gtin'] ?? null,
                    'category' => is_string($item['category'] ?? null) ? $item['category'] : 'Obuća',
                    'color' => $item['color'] ?? null,
                    'description' => strip_tags($item['description'] ?? ''),
                    'image_url' => $this->normalizeImageUrl($imageUrl),
                    'product_url' => $url,
                    'current_price' => $price,
                    'old_price' => $this->oldPrice($crawler, $price),
                    'currency' => $offer['priceCurrency'] ?? 'BAM',
                    'availability' => str_contains(strtolower($offer['availability'] ?? ''), 'instock') ? 'in_stock' : 'unknown',
                    'available_sizes' => $this->sizes($crawler),
                ];
            }
        }

        return null;
    }

    protected function normalizeImageUrl(?string $url): ?string
    {
        return $url;
    }

    protected function sizes(Crawler $crawler): array
    {
        $sizes = [];
        $crawler->filter('[data-productsize-name] .eur-size')->each(function (Crawler $node) use (&$sizes) {
            $this->addSize($sizes, $node->text());
        });
        $crawler->filter('[data-size]:not([disabled]), .size.available, .product-size.available, option:not([disabled])')->each(function (Crawler $node) use (&$sizes) {
            $this->addSize($sizes, $node->attr('data-size') ?? $node->attr('value') ?? $node->text());
        });

        return array_keys($sizes);
    }

    private function addSize(array &$sizes, string $raw): void
    {
        $value = trim(str_replace(',', '.', $raw));
        if (preg_match('/^(?:[1-5][0-9])(?:\.5|\s(?:1|2)\/3)?$/', $value)) {
            $sizes[$value] = true;
        }
    }

    private function oldPrice(Crawler $crawler, float $price): ?float
    {
        foreach (['.old-price', '.regular-price', '[data-productsize-oldprice]'] as $selector) {
            try {
                $node = $crawler->filter($selector)->first();
                $value = $this->decimal($node->attr('data-productsize-oldprice') ?? $node->text());
                if ($value > $price) {
                    return $value;
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function decimal(mixed $value): float
    {
        $value = preg_replace('/[^0-9,.]/', '', (string) $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }

        return (float) str_replace(',', '.', $value);
    }
}

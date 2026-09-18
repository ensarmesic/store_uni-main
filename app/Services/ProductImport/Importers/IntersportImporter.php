<?php

namespace App\Services\ProductImport\Importers;

use App\Models\Offer;
use App\Services\ProductImport\SneakerFilter;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class IntersportImporter extends AbstractHtmlImporter
{
    public function getStoreName(): string
    {
        return 'Intersport BiH';
    }

    public function getStoreSlug(): string
    {
        return 'intersport';
    }

    public function getWebsiteUrl(): string
    {
        return 'https://www.intersport.ba';
    }

    public function fetchProducts(): iterable
    {
        $seen = [];
        $variants = [];
        $pending = [];
        $limit = max(0, (int) env('IMPORT_PRODUCT_LIMIT', 0));
        $pageLimit = max(0, (int) env('IMPORT_PAGE_LIMIT', 0));
        foreach (['muskarci/obuca', 'zene/obuca', 'djeca/obuca'] as $category) {
            $page = 1;
            do {
                $path = $category.($page > 1 ? '?p='.$page : '');
                try {
                    $data = $this->publicApi('fetchCatalogV2', ['path' => $path, 'segmentifyCommonParameters' => []]);
                    $products = $data['products'] ?? [];
                    $total = (int) ($data['numberOfMatchingItems'] ?? 0);
                    $pageSize = max(1, (int) ($data['config']['pageSize'] ?? 42));
                    if ((int) ($data['config']['pageNumber'] ?? 1) !== $page || (! $products && ($page - 1) * $pageSize < $total)) {
                        throw new RuntimeException('Catalog pagination did not return the requested products.');
                    }
                    foreach ($products as $item) {
                        foreach ($item['productVariants'] ?? [] as $variant) {
                            if (! empty($variant['urlKey'])) {
                                $variants[$variant['id']] = $variant;
                            }
                        }
                        if (isset($seen[$item['id']])) {
                            continue;
                        }
                        $seen[$item['id']] = true;
                        if ((new SneakerFilter)->accepts($item)) {
                            $pending[$item['id']] = $item['urlKey'];
                        }
                        if ($limit && count($seen) >= $limit) {
                            $this->reportFailure($path, 'Product limit reached; import is incomplete.');
                            break 3;
                        }
                    }
                    if (PHP_SAPI === 'cli') {
                        echo 'intersport '.$category.' page '.$page.' / '.(int) ceil($total / $pageSize).PHP_EOL;
                    }
                } catch (Throwable $e) {
                    $this->reportFailure($this->getWebsiteUrl().'/'.$path, $e->getMessage());
                    break;
                }
                $hasNext = $page * $pageSize < $total;
                if ($hasNext && $pageLimit && $page >= $pageLimit) {
                    $this->reportFailure($path, 'Page limit reached; import is incomplete.');
                    break;
                }
                $page++;
            } while ($hasNext);
        }
        foreach ($variants as $id => $variant) {
            if (isset($seen[$id]) || ! (new SneakerFilter)->accepts($variant)) {
                continue;
            }
            $pending[$id] = $variant['urlKey'];
        }
        if ($limit) {
            $pending = array_slice($pending, 0, $limit, true);
        }
        if ($this->missingOnly) {
            $existing = Offer::whereHas('store', fn ($store) => $store->where('slug', $this->getStoreSlug()))->pluck('external_id')->all();
            foreach ($existing as $id) {
                unset($pending[$id]);
            }
        }
        $done = 0;
        // Catalog cards do not expose live stock reliably; use product details for every pair.
        foreach (array_chunk($pending, 4, true) as $batch) {
            usleep((int) env('IMPORT_DELAY_MS', 750) * 1000);
            try {
                $responses = Http::pool(function (Pool $pool) use ($batch) {
                    $requests = [];
                    foreach ($batch as $id => $key) {
                        $requests[] = $pool->as((string) $id)
                            ->withOptions(['verify' => config('catalog.ca_bundle') ?: true])
                            ->withHeaders(['User-Agent' => env('IMPORT_USER_AGENT')])->timeout(25)
                            ->post($this->getWebsiteUrl().'/api/intersport/shared/Api/fetchProduct', ['urlKey' => json_encode($key)]);
                    }

                    return $requests;
                });
            } catch (Throwable) {
                $responses = [];
            }
            foreach ($batch as $id => $key) {
                try {
                    $response = $responses[$id] ?? null;
                    $data = $response instanceof Response && $response->successful() ? $response->json() : $this->publicApi('fetchProduct', ['urlKey' => $key]);
                    if (empty($data['product'])) {
                        throw new RuntimeException('No product details.');
                    }
                    $item = $data['product'];
                    $item['urlKey'] = $key;
                    if ($product = $this->sneaker($item)) {
                        yield $product;
                    }
                } catch (Throwable $e) {
                    $this->reportFailure($key, $e->getMessage());
                }
            }
            $done += count($batch);
            if (PHP_SAPI === 'cli' && $done % 100 === 0) {
                echo 'intersport details '.$done.' / '.count($pending).PHP_EOL;
            }
        }
    }

    protected function publicApi(string $method, array $arguments): array
    {
        usleep((int) env('IMPORT_DELAY_MS', 750) * 1000);
        // The public web client serializes each argument as a JSON string.
        $payload = array_map(fn ($value) => json_encode($value, JSON_THROW_ON_ERROR), $arguments);
        $response = Http::withOptions(['verify' => config('catalog.ca_bundle') ?: true])
            ->withHeaders(['User-Agent' => env('IMPORT_USER_AGENT')])
            ->timeout(25)->retry(3, 500)
            ->post($this->getWebsiteUrl().'/api/intersport/shared/Api/'.$method, $payload)
            ->throw()->json();
        if (! is_array($response) || ! str_ends_with($response['$type'] ?? '', '.Success')) {
            throw new RuntimeException('Public catalog API returned no successful response.');
        }

        return $response;
    }

    protected function sneaker(array $item): ?array
    {
        if (! (new SneakerFilter)->accepts($item)) {
            return null;
        }
        $sizes = $item['sizes'] ?? [];
        $available = array_values(array_filter($sizes, fn ($size) => $size['availableForSale'] ?? false));
        $priced = array_values(array_filter($available ?: $sizes, fn ($size) => ($size['price']['price'] ?? 0) > 0));
        if (! $priced) {
            return null;
        }
        $priceOf = fn ($size) => (float) ($size['price']['specialPrice'][0] ?? $size['price']['price']);
        usort($priced, fn ($a, $b) => $priceOf($a) <=> $priceOf($b));
        $price = $priceOf($priced[0]);
        $regular = (float) $priced[0]['price']['price'];
        $color = $item['colors'][0]['name'] ?? null;
        foreach ($item['productVariants'] ?? [] as $variant) {
            if ($variant['id'] === $item['id']) {
                $color = $variant['color'][0]['name'] ?? $color;
            }
        }
        $image = $item['mainImage'] ?? $item['images'][0] ?? null;

        return [
            'store_product_id' => (string) $item['id'],
            'name' => $item['name'], 'name_original' => $item['name'],
            'brand' => $item['googleAnalyticsBrand'][0] ?? $item['brand'][0]['name'] ?? null,
            'sku' => $item['sku'],
            // A model number can cover several colors; it is not an exact MPN.
            'mpn' => $item['catalogNumber'][0] ?? $item['sku'],
            'color' => $color, 'category' => 'Patike',
            'current_price' => $price, 'old_price' => $regular > $price ? $regular : null,
            'currency' => 'BAM', 'availability' => $available ? 'in_stock' : 'out_of_stock',
            'available_sizes' => array_column($available, 'label'),
            'image_url' => $image ? $this->getWebsiteUrl().'/media/catalog/product/'.ltrim($image, '/') : null,
            'product_url' => $this->getWebsiteUrl().'/'.$item['urlKey'],
        ];
    }
}

<?php

namespace App\Services\ProductImport;

use App\Models\ImportRun;
use App\Models\Offer;
use App\Models\OfferVariant;
use App\Models\OfferVariantAvailabilityHistory;
use App\Models\PriceHistory;
use App\Models\VariantPriceHistory;
use App\Models\Product;
use App\Models\ProductMatch;
use App\Models\Store;
use App\Services\ProductImport\Contracts\StoreImporterInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ImportManager
{
    public function __construct(private ProductMatcher $matcher) {}

    public function import(StoreImporterInterface $importer): ImportRun
    {
        $host = strtolower((string) parse_url($importer->getWebsiteUrl(), PHP_URL_HOST));
        abort_unless(in_array($host, config('catalog.allowed_store_hosts', []), true), 422, 'Trgovina nije odobreni BiH izvor.');

        $store = Store::updateOrCreate(['slug' => $importer->getStoreSlug()], [
            'name' => $importer->getStoreName(), 'website_url' => $importer->getWebsiteUrl(), 'is_active' => true,
        ]);
        $run = ImportRun::create(['store_id' => $store->id, 'started_at' => now()]);
        $stats = array_fill_keys(['products_found', 'products_created', 'offers_created', 'offers_updated', 'price_changes', 'failed_products'], 0);
        $errors = [];
        $seenExternalIds = [];
        $fetchCompleted = false;
        if (method_exists($importer, 'resetFailures')) {
            $importer->resetFailures();
        }

        try {
            foreach ($importer->fetchProducts() as $raw) {
                if (! (new SneakerFilter)->accepts($raw)) {
                    continue;
                }
                $raw['category'] = 'Patike';
                $stats['products_found']++;
                try {
                    $seenExternalIds[] = (string) ($raw['store_product_id'] ?? $raw['external_id'] ?? '');
                    $changes = DB::transaction(function () use ($importer, $raw, $store) {
                        $changes = ['products_created' => 0, 'offers_created' => 0, 'offers_updated' => 0, 'price_changes' => 0];
                        $data = $importer->normalize($raw);
                        $old = Offer::where(['store_id' => $store->id, 'external_id' => $data['store_product_id']])->first();
                        $match = $this->matcher->match($data);
                        $attributes = [
                            'brand' => $data['brand'], 'name' => $data['brand'].' '.$data['model'], 'model' => $data['model'],
                            'ean' => $data['ean'] ?? null, 'gtin' => $data['gtin'] ?? null, 'mpn' => $data['mpn'] ?? null,
                            'gender' => $data['gender'] ?? null, 'category' => $data['category'] ?? 'Obuća',
                            'color' => $data['color'] ?? null,
                        ];
                        if ($match->productId) {
                            $product = Product::findOrFail($match->productId);
                        } elseif ($old && $old->product->offers()->count() === 1) {
                            $product = $old->product;
                            $product->update($attributes);
                        } else {
                            $product = Product::create([...$attributes, 'slug' => Str::slug($data['brand'].'-'.$data['model'].'-'.($data['color'] ?? '').' '.Str::random(5))]);
                            $changes['products_created']++;
                        }

                        $offer = Offer::updateOrCreate(['store_id' => $store->id, 'external_id' => $data['store_product_id']], [
                            'product_id' => $product->id, 'external_sku' => $data['sku'] ?? null,
                            'name_original' => $data['name_original'] ?? $data['name'], 'price' => $data['current_price'],
                            'old_price' => $data['old_price'] ?? null, 'currency' => $data['currency'] ?? 'BAM',
                            'availability' => $data['availability'] ?? 'unknown', 'description' => $data['description'] ?? null,
                            'image_url' => $data['image_url'] ?? null, 'product_url' => $data['product_url'], 'last_checked_at' => now(),
                            'last_seen_at' => now(), 'missing_since' => null, 'is_active' => true,
                            'sizes_checked_at' => array_key_exists('available_sizes', $data) ? now() : $old?->sizes_checked_at,
                        ]);
                        $changes[$old ? 'offers_updated' : 'offers_created']++;

                        if (! $old || bccomp((string) $old->price, (string) $offer->price, 2) !== 0) {
                            PriceHistory::create(['offer_id' => $offer->id, 'price' => $offer->price, 'recorded_at' => now()]);
                            $changes['price_changes']++;
                        }

                        $sizes = array_values(array_unique(array_map('strval', $data['available_sizes'] ?? [])));
                        foreach ($sizes as $size) {
                            $variant = $offer->variants()->firstOrNew(['size' => $size]);
                            $wasNew = ! $variant->exists;
                            $variant->availability = 'in_stock';
                            $variant->price = $data['variant_prices'][$size] ?? null;
                            $variant->save();
                            $effectivePrice = (float) ($variant->price ?? $offer->price);
                            if ($effectivePrice > 0) {
                                VariantPriceHistory::updateOrCreate([
                                    'offer_variant_id' => $variant->id, 'observed_on' => now()->toDateString(),
                                ], ['price' => $effectivePrice, 'recorded_at' => now()]);
                            }
                            if ($wasNew || $variant->wasChanged('availability')) {
                                OfferVariantAvailabilityHistory::create([
                                    'offer_variant_id' => $variant->id,
                                    'availability' => 'in_stock',
                                    'recorded_at' => now(),
                                ]);
                            }
                        }
                        if (array_key_exists('available_sizes', $data)) {
                            $offer->variants()->get()->filter(function (OfferVariant $variant) use ($sizes) {
                                return $variant->availability !== 'out_of_stock' && (! $sizes || ! in_array((string) $variant->size, $sizes, true));
                            })->each(function (OfferVariant $variant) {
                                $variant->update(['availability' => 'out_of_stock']);
                                OfferVariantAvailabilityHistory::create([
                                    'offer_variant_id' => $variant->id,
                                    'availability' => 'out_of_stock',
                                    'recorded_at' => now(),
                                ]);
                            });
                        }

                        ProductMatch::updateOrCreate(['offer_id' => $offer->id], [
                            'product_id' => $product->id, 'confidence' => $match->confidence, 'method' => $match->method,
                            'status' => $match->productId ? $match->status : 'confirmed',
                        ]);

                        return $changes;
                    }, 5);
                    foreach ($changes as $key => $value) {
                        $stats[$key] += $value;
                    }
                } catch (Throwable $exception) {
                    $stats['failed_products']++;
                    $errors[] = ($raw['product_url'] ?? '').': '.$exception->getMessage();
                }
            }
            $fetchCompleted = true;
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }

        if ($fetchCompleted) {
            $offers = Offer::where('store_id', $store->id);
            if ($seenExternalIds) {
                $offers->whereNotIn('external_id', array_values(array_unique(array_filter($seenExternalIds))));
            }
            $offers->where('is_active', true)->update(['is_active' => false, 'missing_since' => now()]);
        }

        if (method_exists($importer, 'failures')) {
            $errors = array_merge($errors, $importer->failures());
        }
        if (! $errors && $stats['products_found'] > 0) {
            $store->update(['last_synced_at' => now()]);
        }
        $run->update([...$stats, 'errors' => $errors, 'finished_at' => now()]);
        Cache::flush();

        return $run;
    }
}

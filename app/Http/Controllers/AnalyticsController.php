<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{Cache, DB};

class AnalyticsController
{
    public function index()
    {
        $analytics = Cache::remember('catalog:analytics:v1', 600, function () {
            $overview = DB::table('offers')->selectRaw('COUNT(*) offers, ROUND(AVG(price), 2) avg_price, MIN(price) min_price, MAX(price) max_price')->first();
            $sale = DB::table('offers')->whereNotNull('old_price')->whereColumn('old_price', '>', 'price')
                ->selectRaw('COUNT(*) sale_offers, ROUND(AVG((old_price-price)*100.0/old_price), 1) avg_discount, ROUND(SUM(old_price-price), 2) total_savings')->first();

            $storeStats = DB::table('stores as s')->join('offers as o', 'o.store_id', '=', 's.id')->join('products as p', 'p.id', '=', 'o.product_id')
                ->selectRaw('s.name, s.slug, s.website_url, s.last_synced_at, COUNT(o.id) offers, COUNT(DISTINCT o.product_id) models, COUNT(DISTINCT p.brand) brands, ROUND(AVG(o.price), 2) avg_price, MIN(o.price) min_price, SUM(CASE WHEN o.old_price > o.price THEN 1 ELSE 0 END) sale_offers')
                ->groupBy('s.id')->orderByDesc('offers')->get();

            $brandStats = DB::table('products as p')->join('offers as o', 'o.product_id', '=', 'p.id')
                ->selectRaw('p.brand, COUNT(DISTINCT p.id) models, COUNT(o.id) offers, COUNT(DISTINCT o.store_id) stores, MIN(o.price) min_price, ROUND(AVG(o.price), 2) avg_price')
                ->groupBy('p.brand')->orderByDesc('offers')->limit(14)->get();

            $priceBuckets = DB::table('offers')->selectRaw("CASE
                    WHEN price < 80 THEN 'DO 80 KM'
                    WHEN price < 120 THEN '80–119 KM'
                    WHEN price < 180 THEN '120–179 KM'
                    WHEN price < 250 THEN '180–249 KM'
                    ELSE '250+ KM' END bucket, COUNT(*) total")
                ->groupBy('bucket')->get()->keyBy('bucket');

            $bucketOrder = ['DO 80 KM', '80–119 KM', '120–179 KM', '180–249 KM', '250+ KM'];
            $priceBuckets = collect($bucketOrder)->map(fn ($label) => (object) ['bucket' => $label, 'total' => (int) ($priceBuckets[$label]->total ?? 0)]);

            $genderStats = DB::table('products')->selectRaw("COALESCE(gender, 'unknown') gender, COUNT(*) total")
                ->groupBy('gender')->orderByDesc('total')->get();

            $topDeals = DB::table('offers as o')->join('products as p', 'p.id', '=', 'o.product_id')->join('stores as s', 's.id', '=', 'o.store_id')
                ->whereNotNull('o.old_price')->whereColumn('o.old_price', '>', 'o.price')
                ->selectRaw('p.brand, p.model, p.slug, s.name store_name, o.price, o.old_price, o.image_url, ROUND((o.old_price-o.price)*100.0/o.old_price) discount')
                ->orderByDesc('discount')->orderBy('o.price')->limit(8)->get();

            $topSizes = DB::table('offer_variants')->where('availability', 'in_stock')
                ->selectRaw('size, COUNT(*) total')->groupBy('size')->orderByDesc('total')->limit(12)->get();

            $competition = DB::table('offers')->selectRaw('product_id, COUNT(DISTINCT store_id) stores, MAX(price)-MIN(price) spread')
                ->groupBy('product_id')->havingRaw('COUNT(DISTINCT store_id) > 1');

            $competitionStats = DB::query()->fromSub($competition, 'competition')
                ->selectRaw('COUNT(*) compared_models, ROUND(AVG(spread), 2) avg_spread, MAX(spread) max_spread')->first();

            return [
                'overview' => [
                    'products' => DB::table('products')->count(),
                    'offers' => (int) $overview->offers,
                    'brands' => DB::table('products')->distinct()->count('brand'),
                    'stores' => $storeStats->count(),
                    'avg_price' => (float) $overview->avg_price,
                    'min_price' => (float) $overview->min_price,
                    'max_price' => (float) $overview->max_price,
                    'sale_offers' => (int) $sale->sale_offers,
                    'avg_discount' => (float) $sale->avg_discount,
                    'total_savings' => (float) $sale->total_savings,
                ],
                'stores' => $storeStats,
                'brands' => $brandStats,
                'price_buckets' => $priceBuckets,
                'genders' => $genderStats,
                'deals' => $topDeals,
                'sizes' => $topSizes,
                'competition' => $competitionStats,
            ];
        });

        return view('analytics.index', compact('analytics'));
    }
}

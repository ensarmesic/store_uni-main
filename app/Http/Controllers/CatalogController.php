<?php

namespace App\Http\Controllers;

use App\Models\{Offer, OfferVariant, Product, Store};
use App\Services\{FitRecommendation, NaturalCatalogSearch, ProductPriceInsights};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController
{
    public function index(Request $request)
    {
        $profile = app(\App\Services\ShoppingProfile::class);
        $preferences = $profile->preferences($request);
        $personalBrands = [];
        if ($request->boolean('personal')) {
            if (! array_filter($preferences)) return redirect()->route('shopping.preferences');
            foreach (['size', 'price_max'] as $field) {
                if (! $request->has($field) && ! empty($preferences[$field])) $request->merge([$field => $preferences[$field]]);
            }
            $personalBrands = $preferences['brands'] ?? [];
        }
        if ($request->filled('q')) {
            $natural = app(NaturalCatalogSearch::class)->parse($request->string('q')->toString());
            foreach (['brand', 'gender', 'color', 'size', 'price_min', 'price_max'] as $field) {
                if (! $request->filled($field) && $natural[$field] !== null) $request->merge([$field => $natural[$field]]);
            }
            if (! $request->filled('model') && $natural['term']) $request->merge(['model' => $natural['term']]);
        }

        $products = Cache::remember('catalog:v5:'.sha1(($request->getQueryString() ?? '').json_encode($personalBrands)), 600, function () use ($request, $personalBrands) {
            $offerFilter = $this->offerFilter($request);
            [$priceSql, $priceBindings] = $this->effectivePrice($request);
            $query = Product::query()
                ->select('products.*')
                ->with(['offers' => fn ($offers) => $offerFilter($offers)->with(['store', 'variants'])])
                ->selectSub($offerFilter(Offer::query()->selectRaw('MIN('.$priceSql.')', $priceBindings)->whereColumn('offers.product_id', 'products.id')), 'display_min_price')
                ->withAggregate(['offers as display_store_count' => $offerFilter], \Illuminate\Support\Facades\DB::raw('DISTINCT store_id'), 'count')
                ->selectSub($offerFilter(Offer::query()->selectRaw('MAX(COALESCE(offers.old_price, offers.price) - '.$priceSql.')', $priceBindings)->whereColumn('offers.product_id', 'products.id')), 'display_discount')
                ->whereHas('offers', $offerFilter);
            if ($personalBrands && ! $request->filled('brand')) $query->whereIn('brand', $personalBrands);

            foreach (['brand', 'gender', 'color'] as $field) {
                if ($request->filled($field)) $query->where($field, $request->string($field));
            }
            if ($request->filled('model')) $query->where('model', 'like', '%'.$request->string('model').'%');

            match ($request->input('sort', 'price_asc')) {
                'price_desc' => $query->orderByDesc('display_min_price'),
                'discount' => $query->orderByDesc('display_discount'),
                'name' => $query->orderBy('name'),
                'stores' => $query->orderByDesc('display_store_count'),
                default => $query->orderBy('display_min_price'),
            };

            return $query->orderBy('products.id')->paginate(20)->withQueryString()->fragment('rezultati');
        });

        $filters = Cache::remember('catalog:filters', 1800, fn () => [
            'brands' => Product::whereHas('offers', fn ($offers) => $offers->where('is_active', true))->select('brand')->distinct()->orderBy('brand')->pluck('brand'),
            'colors' => Product::whereHas('offers', fn ($offers) => $offers->where('is_active', true))->whereNotNull('color')->select('color')->distinct()->orderBy('color')->pluck('color'),
            'sizes' => OfferVariant::where('availability', 'in_stock')->whereHas('offer', fn ($offers) => $offers->where('is_active', true))->select('size')->distinct()->pluck('size')->sortBy(fn ($size) => (float) $size)->values(),
            'stores' => Store::where('is_active', true)->whereHas('offers', fn ($offers) => $offers->where('is_active', true))->orderBy('name')->get(),
        ]);
        $stats = Cache::remember('catalog:stats:v2', 600, function () {
            $sale = Offer::where('is_active', true)->whereNotNull('old_price')->whereColumn('old_price', '>', 'price')->where('price', '>', 0);

            return [
                    'products' => Product::whereHas('offers', fn ($offers) => $offers->where('is_active', true))->count(),
                    'offers' => Offer::where('is_active', true)->count(),
                    'stores' => Store::whereHas('offers', fn ($offers) => $offers->where('is_active', true))->count(),
                'brands' => Product::whereHas('offers', fn ($offers) => $offers->where('is_active', true))->distinct('brand')->count('brand'),
                'sale_offers' => (clone $sale)->count(),
                'best_discount' => round((float) ((clone $sale)
                    ->selectRaw('MAX((old_price-price)*100.0/old_price) value')->value('value') ?? 0)),
            ];
        });

        $popularProducts = Cache::remember('catalog:popular:v3', 600, fn () => Product::query()
            ->select('products.*')
            ->selectSub(\Illuminate\Support\Facades\DB::table('product_interactions')
                ->selectRaw("COALESCE(SUM(CASE kind WHEN 'outbound' THEN 5 WHEN 'favorite' THEN 4 WHEN 'alert' THEN 4 WHEN 'compare' THEN 2 ELSE 1 END), 0)")
                ->whereColumn('product_id', 'products.id')->where('observed_on', '>=', now()->subDays(30)->toDateString()), 'popularity_score')
            ->with(['offers' => fn ($offers) => $offers->where('is_active', true)->with(['store', 'variants'])])
            ->withMin(['offers as display_min_price' => fn ($offers) => $offers->where('is_active', true)], 'price')
            ->withCount(['offers' => fn ($offers) => $offers->where('is_active', true)])
            ->whereHas('offers', fn ($offers) => $offers->where('is_active', true))
            ->orderByDesc('popularity_score')
            ->orderByDesc('offers_count')
            ->limit(40)
            ->get()
            ->unique(fn (Product $product) => $product->brand.'|'.$product->model)
            ->take(4)
            ->values());

        $favoriteIds = $profile->favoriteIds($request);
        return view('catalog.index', compact('products', 'filters', 'stats', 'popularProducts', 'favoriteIds', 'preferences'));
    }

    public function show(Request $request, string $slug, ProductPriceInsights $priceInsights, FitRecommendation $fitRecommendation)
    {
        $request->validate(['days' => ['nullable', 'in:30,90'], 'size' => ['nullable', 'string', 'max:20']]);
        $product = Product::where('slug', $slug)->with([
            'offers' => fn ($query) => $query->where('is_active', true)->orderBy('price'),
            'offers.store', 'offers.variants', 'offers.priceHistories',
        ])->firstOrFail();

        app(\App\Services\ProductInteractions::class)->record($request, $product, 'view');
        $insights = $priceInsights->for($product, $request->input('size'));
        $fit = ($request->session()->get('fit_passport_key') || $request->session()->get('user_id'))
            ? $fitRecommendation->for($product, (string) $request->session()->get('fit_passport_key'), $request->session()->get('user_id'))
            : null;

        $historyChart = app(\App\Services\PriceHistoryChart::class)->for($product, (int) $request->input('days', 90), $request->input('size'));
        $favoriteIds = app(\App\Services\ShoppingProfile::class)->favoriteIds($request);
        $selectedOffers = app(\App\Services\OfferSelection::class)->for($product, $request->input('size'));
        $decision = app(\App\Services\PurchaseDecision::class)->for($product, $request->input('size'), $fit);
        $feedback = \App\Models\PurchaseFeedback::where('product_id', $product->id)->latest('updated_at')->get()->unique('owner_key');
        $community = ['profiles' => $feedback->count(), 'kept' => $feedback->where('outcome', 'kept')->count(), 'returned' => $feedback->where('outcome', 'returned')->count(), 'just_right' => $feedback->where('fit', 'just_right')->count()];
        return view('catalog.show', compact('product', 'insights', 'fit', 'historyChart', 'favoriteIds', 'selectedOffers', 'decision', 'community'));
    }

    private function offerFilter(Request $request): \Closure
    {
        return function ($query) use ($request) {
            $query->where('is_active', true);
            if ($request->filled('store')) $query->whereHas('store', fn ($store) => $store->where('slug', $request->string('store')));
            if ($request->filled('size')) $query->whereHas('variants', fn ($variant) => $variant->where('size', $request->string('size'))->where('availability', 'in_stock'));
            [$priceSql, $priceBindings] = $this->effectivePrice($request);
            if ($request->filled('price_min')) $query->whereRaw($priceSql.' >= ?', [...$priceBindings, (float) $request->input('price_min')]);
            if ($request->filled('price_max')) $query->whereRaw($priceSql.' <= ?', [...$priceBindings, (float) $request->input('price_max')]);
            if ($request->boolean('available')) $query->where('availability', 'in_stock');
            if ($request->boolean('sale')) $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'price');
            return $query;
        };
    }

    private function effectivePrice(Request $request): array
    {
        if (! $request->filled('size')) return ['offers.price', []];
        return [
            "COALESCE((SELECT MIN(COALESCE(offer_variants.price, offers.price)) FROM offer_variants WHERE offer_variants.offer_id = offers.id AND offer_variants.size = ? AND offer_variants.availability = 'in_stock'), offers.price)",
            [(string) $request->input('size')],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\{Offer, OfferVariant, Product, Store};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DealsController
{
    public function index(Request $request)
    {
        $query = Offer::query()
            ->select('offers.*')
            ->selectRaw('ROUND((old_price - price) * 100.0 / old_price, 1) as discount_percent')
            ->selectRaw('ROUND(old_price - price, 2) as savings')
            ->with(['product', 'store', 'variants' => fn ($variants) => $variants
                ->where('availability', 'in_stock')->orderByRaw('CAST(size AS DECIMAL(5,1))')])
            ->whereNotNull('old_price')
            ->whereColumn('old_price', '>', 'price')
                ->where('price', '>', 0)
                ->where('is_active', true);

        if ($request->filled('brand')) {
            $query->whereHas('product', fn ($product) => $product->where('brand', (string) $request->input('brand')));
        }

        if ($request->filled('store')) {
            $query->whereHas('store', fn ($store) => $store->where('slug', (string) $request->input('store')));
        }

        if ($request->filled('size')) {
            $query->whereHas('variants', fn ($variant) => $variant
                ->where('size', (string) $request->input('size'))->where('availability', 'in_stock'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', max(0, (float) $request->input('max_price')));
        }

        if ($request->filled('min_discount')) {
            $discount = min(95, max(0, (float) $request->input('min_discount')));
            $query->whereRaw('(old_price - price) * 100.0 >= old_price * ?', [$discount]);
        }

        if ($request->filled('q')) {
            $term = '%'.(string) $request->input('q').'%';
            $query->whereHas('product', fn ($product) => $product
                ->where(fn ($match) => $match->where('name', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('brand', 'like', $term)));
        }

        match ($request->input('sort', 'discount')) {
            'saving' => $query->orderByDesc('savings')->orderBy('price'),
            'price' => $query->orderBy('price')->orderByDesc('discount_percent'),
            'newest' => $query->orderByDesc('last_checked_at'),
            default => $query->orderByDesc('discount_percent')->orderByDesc('savings'),
        };

        $deals = $query->paginate(24)->withQueryString();

        $filters = Cache::remember('deals:filters:v1', 1800, fn () => [
            'brands' => Product::whereHas('offers', fn ($offer) => $offer
                ->where('is_active', true)->whereNotNull('old_price')->whereColumn('old_price', '>', 'price'))
                ->distinct()->orderBy('brand')->pluck('brand'),
            'stores' => Store::whereHas('offers', fn ($offer) => $offer
                ->where('is_active', true)->whereNotNull('old_price')->whereColumn('old_price', '>', 'price'))
                ->orderBy('name')->get(),
            'sizes' => OfferVariant::where('availability', 'in_stock')
                ->whereHas('offer', fn ($offer) => $offer
                    ->where('is_active', true)
                    ->whereNotNull('old_price')->whereColumn('old_price', '>', 'price'))
                ->distinct()->pluck('size')->sortBy(fn ($size) => (float) $size)->values(),
        ]);

        $radar = Cache::remember('deals:stats:v1', 600, function () {
            $sale = Offer::where('is_active', true)->whereNotNull('old_price')->whereColumn('old_price', '>', 'price')->where('price', '>', 0);
            $best = (clone $sale)->orderByRaw('(old_price-price)*100.0/old_price DESC')->first();

            return [
                'total' => (clone $sale)->count(),
                'average' => (float) ((clone $sale)->selectRaw('AVG((old_price-price)*100.0/old_price) value')->value('value') ?? 0),
                'savings' => (float) ((clone $sale)->selectRaw('SUM(old_price-price) value')->value('value') ?? 0),
                'best' => $best ? round(($best->old_price - $best->price) * 100 / $best->old_price) : 0,
            ];
        });

        return view('deals.index', compact('deals', 'filters', 'radar'));
    }
}

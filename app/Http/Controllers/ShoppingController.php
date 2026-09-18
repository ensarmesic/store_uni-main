<?php

namespace App\Http\Controllers;

use App\Models\{Favorite, Product, User};
use App\Services\{OfferSelection, ShoppingProfile};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShoppingController
{
    public function favorites(Request $request, ShoppingProfile $profile, OfferSelection $selection)
    {
        $request->validate(['size' => ['nullable', 'string', 'max:20']]);
        $products = Product::whereIn('id', $profile->favoriteIds($request))->with(['offers.store', 'offers.variants'])->orderByDesc('id')->paginate(12)->withQueryString();
        $size = $request->input('size', $profile->preferences($request)['size'] ?? null);
        $offers = $products->getCollection()->mapWithKeys(fn ($product) => [$product->id => $selection->for($product, $size)]);
        return view('shopping.favorites', compact('products', 'size', 'offers'));
    }

    public function saveFavorite(Request $request, Product $product, ShoppingProfile $profile)
    {
        Favorite::firstOrCreate(['owner_key' => $profile->owner($request), 'product_id' => $product->id]);
        app(\App\Services\ProductInteractions::class)->record($request, $product, 'favorite');
        return back()->with('status', 'Model je sačuvan u Moju listu.');
    }

    public function removeFavorite(Request $request, Product $product, ShoppingProfile $profile)
    {
        Favorite::where('owner_key', $profile->owner($request))->where('product_id', $product->id)->delete();
        return back()->with('status', 'Model je uklonjen iz Moje liste.');
    }

    public function compare(Request $request, OfferSelection $selection, ShoppingProfile $profile)
    {
        $request->validate([
            'size' => ['nullable', 'string', 'max:20'],
            'models' => ['sometimes', 'array', 'min:1', 'max:3'],
            'models.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        $shared = $request->has('models');
        $ids = $shared ? array_map('intval', $request->input('models')) : $request->session()->get('comparison', []);
        $products = Product::whereIn('id', $ids)->with(['offers.store', 'offers.variants'])->get()->sortBy(fn ($product) => array_search($product->id, $ids))->values();
        $size = $request->input('size', $shared ? null : ($profile->preferences($request)['size'] ?? null));
        $offers = $products->mapWithKeys(fn ($product) => [$product->id => $selection->for($product, $size)]);
        $sizes = $products->flatMap->offers->flatMap->variants->where('availability', 'in_stock')->pluck('size')->unique()->sortBy(fn ($s) => (float) $s);
        $comparisonQuery = $shared ? ['models' => $products->pluck('id')->all()] : [];
        $shareUrl = route('compare', ['models' => $products->pluck('id')->all(), 'size' => $size ?? '']);
        $bestPrices = $offers->map(fn ($items) => $items->first()['price'] ?? null)->filter(fn ($price) => $price !== null);
        $lowestPrice = $bestPrices->min();
        $missingModels = count($ids) - $products->count();
        return view('shopping.compare', compact('products', 'size', 'offers', 'sizes', 'shared', 'comparisonQuery', 'shareUrl', 'bestPrices', 'lowestPrice', 'missingModels'));
    }

    public function addComparison(Request $request, Product $product)
    {
        $ids = $request->session()->get('comparison', []);
        if (in_array($product->id, $ids)) return back();
        if (count($ids) >= 3) return back()->withErrors(['comparison' => 'Možeš porediti do 3 modela. Ukloni jedan iz poređenja pa dodaj novi.']);
        $request->session()->put('comparison', [...$ids, $product->id]);
        app(\App\Services\ProductInteractions::class)->record($request, $product, 'compare');
        return back()->with('status', 'Model je dodan u poređenje.');
    }

    public function removeComparison(Request $request, Product $product)
    {
        $request->session()->put('comparison', array_values(array_diff($request->session()->get('comparison', []), [$product->id])));
        return back();
    }

    public function preferences(Request $request, ShoppingProfile $profile)
    {
        $preferences = $profile->preferences($request);
        $brands = Product::distinct()->orderBy('brand')->pluck('brand');
        return view('shopping.preferences', compact('preferences', 'brands'));
    }

    public function savePreferences(Request $request, ShoppingProfile $profile)
    {
        $data = $request->validate([
            'size' => ['nullable', 'string', 'max:20', 'regex:/^\d{2}(?:[.,]\d|\s[12]\/3)?$/'],
            'brands' => ['nullable', 'array', 'max:10'],
            'brands.*' => ['string', 'distinct', Rule::exists('products', 'brand')],
            'price_max' => ['nullable', 'numeric', 'min:1', 'max:10000'],
        ], ['size.regex' => 'Unesi EU broj, npr. 43, 42.5 ili 42 2/3.', 'brands.max' => 'Odaberi najviše 10 brendova.']);
        $data['size'] = isset($data['size']) ? str_replace(',', '.', $data['size']) : null;
        $data['brands'] = $data['brands'] ?? [];
        if ($id = $request->session()->get('user_id')) User::findOrFail($id)->update(['shopping_preferences' => $data]);
        else $request->session()->put('shopping_preferences', $data);
        return redirect()->route('shopping.preferences')->with('status', 'Tvoji filteri su sačuvani. Otvori „Moje parove“ da ih primijeniš.');
    }
}

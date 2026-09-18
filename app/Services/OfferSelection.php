<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class OfferSelection
{
    public function for(Product $product, ?string $size = null): Collection
    {
        $product->loadMissing('offers.store', 'offers.variants');
        return $product->offers->where('is_active', true)->map(function ($offer) use ($size) {
            $variant = $size ? $offer->variants->first(fn ($v) => $v->size === $size && $v->availability === 'in_stock') : null;
            if ($size ? ! $variant : $offer->availability !== 'in_stock') return null;
            $price = (float) ($variant?->price ?? $offer->price);
            if ($price <= 0) return null;
            return ['offer' => $offer, 'price' => $price];
        })->filter()->sortBy('price')->values();
    }
}

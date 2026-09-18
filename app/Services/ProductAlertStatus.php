<?php

namespace App\Services;

use App\Models\ProductAlert;

class ProductAlertStatus
{
    public function currentPrice(ProductAlert $alert): ?float
    {
        $alert->loadMissing('product.offers.variants');
        $prices = $alert->product->offers->where('is_active', true)->map(function ($offer) use ($alert) {
            if ($alert->size) {
                $variant = $offer->variants->first(fn ($variant) => $variant->size == $alert->size && $variant->availability === 'in_stock');
                return $variant ? (float) ($variant->price ?? $offer->price) : null;
            }

            return $offer->availability === 'in_stock' ? (float) $offer->price : null;
        })->filter(fn ($price) => $price !== null && $price > 0);

        return $prices->isEmpty() ? null : (float) $prices->min();
    }

    public function check(ProductAlert $alert, ?float $price): bool
    {
        if (! $alert->is_active || $price === null) return false;
        if ($alert->type === 'price' && $price > (float) $alert->target_price) return false;

        $alert->update(['is_active' => false, 'triggered_at' => now()]);

        return true;
    }
}

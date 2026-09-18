<?php

namespace App\Services;

use App\Models\{Offer, OfferVariantAvailabilityHistory, Product};

class StockPressure
{
    public function for(Product $product, ?string $size): array
    {
        $result = ['size' => $size, 'current_stores' => 0, 'total_stores' => 0, 'stale_stores' => 0,
            'score' => null, 'label' => 'Nema dovoljno podataka', 'periods' => []];
        if (! $size) return $result;

        // Missing listings and stale observations are unknown, never a stock-out signal.
        $offers = Offer::where('product_id', $product->id)->where('is_active', true)
            ->whereHas('store', fn ($query) => $query->where('is_active', true))
            ->with(['variants' => fn ($query) => $query->where('size', $size)])->get();
        $fresh = $offers->filter(fn ($offer) => $offer->last_checked_at?->between(now()->subHours(48), now()) &&
            $offer->sizes_checked_at?->between(now()->subHours(48), now()));
        $result['stale_stores'] = $offers->diff($fresh)->pluck('store_id')->unique()->count();
        $result['total_stores'] = $fresh->pluck('store_id')->unique()->count();
        $variants = $fresh->flatMap->variants;
        $storeByVariant = $fresh->flatMap(fn ($offer) => $offer->variants->map(fn ($variant) => ['id' => $variant->id, 'store' => $offer->store_id]))->pluck('store', 'id');
        $result['current_stores'] = $variants->where('availability', 'in_stock')->map(fn ($variant) => $storeByVariant[$variant->id])->unique()->count();
        $history = OfferVariantAvailabilityHistory::whereIn('offer_variant_id', $variants->pluck('id'))
            ->where('recorded_at', '<=', now())->orderBy('recorded_at')->orderBy('id')->get()->groupBy('offer_variant_id');

        foreach ([3, 7, 14] as $days) {
            $baseline = collect();
            $current = collect();
            $knownStores = collect();
            foreach ($fresh->groupBy('store_id') as $storeId => $storeOffers) {
                $storeVariants = $storeOffers->flatMap->variants;
                if ($storeVariants->isEmpty()) continue;
                $states = $storeVariants->map(fn ($variant) => ($history[$variant->id] ?? collect())
                    ->last(fn ($event) => $event->recorded_at->lte(now()->subDays($days)))?->availability);
                // Compare the same observed stores at both dates, excluding unknown states.
                if ($states->contains(fn ($state) => ! in_array($state, ['in_stock', 'out_of_stock'], true)) ||
                    $storeVariants->contains(fn ($variant) => ! in_array($variant->availability, ['in_stock', 'out_of_stock'], true))) continue;
                $knownStores->push($storeId);
                if ($states->contains('in_stock')) $baseline->push($storeId);
                if ($storeVariants->contains('availability', 'in_stock')) $current->push($storeId);
            }
            $lost = $baseline->diff($current)->count();
            $result['periods'][$days] = ['known_stores' => $knownStores->count(), 'before' => $baseline->count(),
                'now' => $current->count(), 'lost' => $lost, 'restocked' => $current->diff($baseline)->count(),
                'drop_percent' => $baseline->isNotEmpty() ? (int) round(max(0, $baseline->count() - $current->count()) / $baseline->count() * 100) : null];
        }
        $baseline = $result['periods'][7];
        if ($baseline['before'] >= 2) {
            $result['score'] = $baseline['drop_percent'];
            $result['label'] = match (true) {
                $result['score'] >= 50 => 'Visok', $result['score'] >= 25 => 'Umjeren', default => 'Nizak',
            };
        }
        return $result;
    }
}

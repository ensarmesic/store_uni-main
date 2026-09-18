<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PriceHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductPriceInsights
{
    public function for(Product $product, ?string $size = null): array
    {
        $now = now();
        $history = PriceHistory::query()
            ->join('offers', 'offers.id', '=', 'price_histories.offer_id')
            ->where('offers.product_id', $product->id)
            ->where('price_histories.price', '>', 0)
            ->select(['price_histories.price', 'price_histories.recorded_at'])
            ->orderBy('price_histories.recorded_at')
            ->get();

        $currentPrices = $product->offers
            ->where('is_active', true)
            ->map(fn ($offer) => $this->priceForSize($offer, $size))
            ->filter(fn ($price) => $price > 0)
            ->values();
        $current = $currentPrices->min();

        $periods = [30, 90, 180];
        $lows = [];
        $averages = [];
        foreach ($periods as $days) {
            $prices = $this->pricesSince($history, $now->copy()->subDays($days));
            $lows[$days] = $prices->min();
            $averages[$days] = $prices->avg();
        }

        $allPrices = $history->pluck('price')->map(fn ($price) => (float) $price);
        $percentile = $current && $allPrices->isNotEmpty()
            ? round($allPrices->filter(fn ($price) => $price <= $current)->count() / $allPrices->count() * 100)
            : null;

        return [
            'current' => $current,
            'low_30' => $lows[30],
            'low_90' => $lows[90],
            'low_180' => $lows[180],
            'average_30' => $averages[30],
            'average_90' => $averages[90],
            'average_180' => $averages[180],
            'historical_low' => $allPrices->min(),
            'percentile' => $percentile,
            'deal_score' => $this->dealScore($current, $averages[90], $lows[90]),
            'store_count' => $currentPrices->count(),
            'size' => $size,
        ];
    }

    private function pricesSince(Collection $history, Carbon $since): Collection
    {
        return $history
            ->filter(fn ($entry) => Carbon::parse($entry->recorded_at)->greaterThanOrEqualTo($since))
            ->pluck('price')
            ->map(fn ($price) => (float) $price)
            ->filter(fn ($price) => $price > 0)
            ->values();
    }

    private function priceForSize($offer, ?string $size): float
    {
        if ($size) {
            $variant = $offer->variants->first(fn ($variant) => $variant->size == $size && $variant->availability === 'in_stock');
            if (! $variant) return 0;
            if ($variant && $variant->price !== null) {
                return (float) $variant->price;
            }
        }

        if (! $size && $offer->availability !== 'in_stock') return 0;

        return (float) $offer->price;
    }

    private function dealScore(?float $current, ?float $average90, ?float $low90): ?int
    {
        if (! $current || ! $average90) {
            return null;
        }

        $discountFromAverage = max(0, min(1, ($average90 - $current) / $average90));
        $score = 50 + round($discountFromAverage * 60);
        if ($low90 && $current <= $low90 * 1.03) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }
}

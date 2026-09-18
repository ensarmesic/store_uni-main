<?php

namespace App\Services;

use App\Models\{PriceHistory, Product, VariantPriceHistory};

class PriceHistoryChart
{
    public function for(Product $product, int $days, ?string $size = null): array
    {
        $start = now()->subDays($days)->startOfDay();
        $query = $size ? VariantPriceHistory::query()
            ->join('offer_variants', 'offer_variants.id', '=', 'variant_price_histories.offer_variant_id')
            ->join('offers', 'offers.id', '=', 'offer_variants.offer_id')->where('offer_variants.size', $size)
            : PriceHistory::query()->join('offers', 'offers.id', '=', 'price_histories.offer_id');
        $table = $size ? 'variant_price_histories' : 'price_histories';
        $rows = $query
            ->join('stores', 'stores.id', '=', 'offers.store_id')
            ->where('offers.product_id', $product->id)->where($table.'.price', '>', 0)
            ->whereBetween('recorded_at', [$start, now()])
            ->orderBy('recorded_at')
            ->get(['stores.id as store_id', 'stores.name as store_name', $table.'.price', 'recorded_at']);
        $low = (float) ($rows->min('price') ?? 0);
        $high = (float) ($rows->max('price') ?? 0);
        $colors = ['#3155ff', '#e44900', '#00856a', '#8b39b8', '#aa6500', '#007baf', '#bf2960', '#505829'];
        $series = $rows->groupBy('store_id')->values()->map(function ($entries, $index) use ($start, $low, $high, $colors) {
            $points = $entries->groupBy(fn ($row) => $row->recorded_at->format('Y-m-d'))->map(function ($dayRows, $date) use ($start, $low, $high) {
                $price = (float) $dayRows->min('price');
                $time = \Carbon\Carbon::parse($date);
                return [
                    'date' => $date, 'label' => $time->format('d.m.Y.'), 'price' => $price,
                    'x' => round(55 + ($time->timestamp - $start->timestamp) / max(1, now()->timestamp - $start->timestamp) * 710, 2),
                    'y' => round($high === $low ? 120 : 210 - ($price - $low) / ($high - $low) * 180, 2),
                ];
            })->values();
            return ['name' => $entries->first()->store_name, 'color' => $colors[$index % count($colors)], 'points' => $points];
        });
        return ['series' => $series, 'low' => $low, 'high' => $high, 'days' => $days, 'start' => $start, 'size' => $size,
            'hasTrend' => $series->contains(fn ($line) => $line['points']->count() >= 2),
            'lastChecked' => $product->offers->max('last_checked_at')];
    }
}

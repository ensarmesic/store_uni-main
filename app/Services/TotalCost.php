<?php

namespace App\Services;

use App\Models\Offer;
use Carbon\Carbon;

class TotalCost
{
    public function for(Offer $offer, float $price): array
    {
        $rule = config('delivery.stores.'.$offer->store->slug);
        $result = ['item' => $price, 'shipping' => null, 'total' => null, 'pickup_total' => null, 'pickup_note' => null, 'source' => null];
        if ($offer->currency !== 'BAM') return $result;
        if (! $rule || empty($rule['verified_at']) || empty($rule['source_url'])) return $result;
        try { $verified = Carbon::parse($rule['verified_at']); } catch (\Throwable) { return $result; }
        if ($verified->lt(now()->subDays(90)) || $verified->isFuture() || ! str_starts_with($rule['source_url'], 'https://')) return $result;
        if (isset($rule['flat_fee']) && is_numeric($rule['flat_fee']) && $rule['flat_fee'] >= 0 && (! isset($rule['unknown_at']) || abs($price - $rule['unknown_at']) > .001)) {
            $free = (isset($rule['free_from']) && $price >= $rule['free_from']) || (isset($rule['free_above']) && $price > $rule['free_above']);
            $result['shipping'] = $free ? 0.0 : (float) $rule['flat_fee'];
            $result['total'] = round($price + $result['shipping'], 2);
        }
        if (($rule['free_pickup'] ?? false) === true) $result['pickup_total'] = $price;
        $result['pickup_note'] = $rule['pickup_note'] ?? null;
        $result['source'] = $rule['source_url'];
        return $result;
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;

class ShoppingAgent
{
    public function analyze(Request $request): array
    {
        $intent = app(ShoppingIntent::class)->parse((string) $request->input('message'));
        $preferences = app(ShoppingProfile::class)->preferences($request);
        $intent['size'] = $request->input('size') ?: $intent['size'] ?: ($preferences['size'] ?? null);
        if ($intent['size'] && EuSize::number($intent['size']) !== null) $intent['size'] = EuSize::label(EuSize::number($intent['size']));
        $intent['budget'] = $request->input('budget') ?: $intent['budget'] ?: ($preferences['price_max'] ?? null);
        $query = Product::whereHas('offers', fn ($q) => $q->where('is_active', true)->where('last_checked_at', '>=', now()->subHours(48)))
            ->with(['offers.store','offers.variants']);
        if ($intent['brand']) $query->whereRaw('UPPER(brand) = ?', [$intent['brand']]);
        elseif (! empty($preferences['brands'])) $query->whereIn('brand', $preferences['brands']);
        if ($intent['colors']) $query->where(function ($q) use ($intent) {
            foreach ($intent['colors'] as $color) $q->orWhereRaw('LOWER(color) LIKE ?', ['%'.$color.'%']);
        });
        if ($intent['size']) $query->whereHas('offers', fn ($q) => $q->where('is_active', true)->whereHas('variants', fn ($v) => $v->where('size', $intent['size'])->where('availability', 'in_stock')));
        $query->withMin(['offers as candidate_price' => fn ($q) => $q->where('is_active', true)], 'price')->orderBy('candidate_price')->orderBy('id');
        $candidateCount = (clone $query)->count();
        $results = collect();
        foreach ($query->limit(60)->get() as $product) {
            $fit = app(FitRecommendation::class)->for($product, (string) $request->session()->get('fit_passport_key'), $request->session()->get('user_id'), $intent['anchors']);
            $size = $intent['size'] ?: (($fit['suitable'] ?? false) ? $fit['size'] : null);
            if (! $size) continue;
            $decision = app(PurchaseDecision::class)->for($product, $size, $fit);
            if (! $decision['current'] || $decision['status'] === 'check_fit') continue;
            if ($intent['budget'] && $decision['current'] > $intent['budget']) continue;
            $fitScore = $fit && $fit['size'] === $size ? $fit['confidence'] : 0;
            $score = $fitScore + ($decision['status'] === 'buy' ? 30 : 0) + ($decision['status'] === 'wait' && ! $intent['can_wait'] ? -20 : 0);
            $results->push(compact('product', 'fit', 'size', 'decision', 'score'));
        }
        return ['intent' => $intent, 'results' => $results->sortByDesc('score')->take(6)->values(), 'candidateCount' => $candidateCount];
    }
}

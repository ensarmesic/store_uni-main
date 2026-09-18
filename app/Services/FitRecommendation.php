<?php

namespace App\Services;

use App\Models\{FitPassportEntry, Product, PurchaseFeedback};
use Illuminate\Support\Facades\DB;

class FitRecommendation
{
    public function for(Product $product, string $sessionKey, ?int $userId = null, array $anchors = []): ?array
    {
        $entries = FitPassportEntry::query()->where(function ($query) use ($sessionKey, $userId) {
            $query->whereRaw('1 = 0');
            if ($sessionKey !== '') $query->orWhere('session_id', $sessionKey);
            if ($userId) $query->orWhere('user_id', $userId);
        })->with('product:id,brand,model,name')->latest('updated_at')->get();
        if ($userId) {
            foreach (PurchaseFeedback::where('owner_key', 'user:'.$userId)->with('product')->latest('updated_at')->get() as $feedback) {
                if ($entries->contains('product_id', $feedback->product_id)) continue;
                $entry = new FitPassportEntry(['product_id' => $feedback->product_id, 'size' => $feedback->size, 'fit' => $feedback->fit]);
                $entry->setRelation('product', $feedback->product);
                $entries->push($entry);
            }
        }
        foreach ($anchors as $anchor) {
            $entry = new FitPassportEntry($anchor);
            $entry->setRelation('product', Product::find($anchor['product_id']));
            $entries = $entries->reject(fn ($existing) => $existing->product_id === $entry->product_id)->prepend($entry);
        }
        $exact = $entries->firstWhere('product_id', $product->id);
        if ($exact) return [
            'size' => $exact->size, 'confidence' => $exact->fit === 'just_right' ? 100 : 0,
            'suitable' => $exact->fit === 'just_right', 'source' => 'personal', 'samples' => 1,
            'reason' => 'Tvoj zapis za ovaj model: '.match ($exact->fit) {
                'just_right' => 'broj odgovara taman.', 'tight' => 'broj je tijesan; nemamo potvrdu za veći broj.',
                default => 'model je širok; nemamo potvrdu da manji broj rješava fit.',
            },
        ];
        if ($entries->isEmpty()) return null;

        // Live same-owner pairs form the graph; edited/deleted feedback immediately stops voting.
        $pairs = DB::table('fit_passport_entries as source')
            ->join('fit_passport_entries as target', function ($join) {
                $join->on(function ($owners) {
                    $owners->on('source.user_id', '=', 'target.user_id')->orOn(function ($guest) {
                        $guest->on('source.session_id', '=', 'target.session_id')->whereNull('source.user_id')->whereNull('target.user_id');
                    });
                });
            })->whereIn('source.product_id', $entries->pluck('product_id'))
            ->where('target.product_id', $product->id)->where('target.fit', 'just_right')
            ->select(['source.product_id', 'source.size as source_size', 'source.fit as source_fit',
                'source.user_id', 'source.session_id', 'target.size as target_size', 'target.updated_at'])
            ->orderByDesc('target.updated_at')->get();
        $purchasePairs = DB::table('purchase_feedback as source')->join('purchase_feedback as target', 'source.owner_key', '=', 'target.owner_key')
            ->where('source.owner_key', 'like', 'user:%')->whereIn('source.product_id', $entries->pluck('product_id'))
            ->where('target.product_id', $product->id)->where('target.outcome', 'kept')->where('target.fit', 'just_right')
            ->select(['source.product_id', 'source.size as source_size', 'source.fit as source_fit', 'source.owner_key', 'target.size as target_size', 'target.updated_at'])
            ->orderByDesc('target.updated_at')->get()->map(function ($pair) {
                $pair->user_id = (int) substr($pair->owner_key, 5); $pair->session_id = '';
                return $pair;
            });
        $pairs = $pairs->concat($purchasePairs);
        $votes = [];
        foreach ($pairs as $pair) {
            if (($userId && (int) $pair->user_id === $userId) || ($sessionKey !== '' && $pair->session_id === $sessionKey)) continue;
            $anchor = $entries->first(fn ($entry) => $entry->product_id === $pair->product_id && $entry->fit === $pair->source_fit);
            if (! $anchor) continue;
            $a = EuSize::number($anchor->size); $b = EuSize::number($pair->source_size); $c = EuSize::number($pair->target_size);
            if ($a === null || $b === null || $c === null || abs($c - $b) > 2) continue;
            $owner = $pair->user_id ? 'user:'.$pair->user_id : 'guest:'.$pair->session_id;
            if (isset($votes[$owner])) continue;
            $predicted = $a + ($c - $b);
            if ($predicted >= 15 && $predicted <= 55) $votes[$owner] = EuSize::label($predicted);
        }
        if (count($votes) >= 3) {
            $counts = array_count_values($votes); arsort($counts);
            $size = (string) array_key_first($counts); $support = $counts[$size]; $agreement = $support / count($votes);
            if ($support >= 3 && $agreement >= .6) return [
                'size' => $size, 'confidence' => (int) min(90, round(40 + 35 * $agreement + min(15, count($votes)))),
                'suitable' => true, 'source' => 'model_graph', 'samples' => count($votes), 'agreement' => (int) round($agreement * 100),
                'reason' => 'Relativna razlika između tvojih poznatih modela i ovog modela: '.$support.' od '.count($votes).
                    ' drugih profila podržava ovaj broj. Uvažavamo isti opis fita; rezultat je procjena, ne izmjerena vjerovatnoća.',
            ];
        }
        $brandEntry = $entries->first(fn ($entry) => $entry->product?->brand === $product->brand && $entry->fit === 'just_right');
        if (! $brandEntry) return null;
        return ['size' => $brandEntry->size, 'confidence' => 30, 'suitable' => true, 'source' => 'personal_brand', 'samples' => 1,
            'reason' => 'Početna orijentacija iz tvog modela brenda '.$product->brand.'. Nema dovoljno odnosa između modela za pouzdaniju preporuku.'];
    }
}

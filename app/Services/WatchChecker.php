<?php

namespace App\Services;

use App\Models\{ShoppingWatch, WatchNotification};
use Illuminate\Support\Facades\DB;

class WatchChecker
{
    public function check(ShoppingWatch $watch): bool
    {
        if (! $watch->is_active) return false;
        $userId = str_starts_with($watch->owner_key, 'user:') ? (int) substr($watch->owner_key, 5) : null;
        $fit = app(FitRecommendation::class)->for($watch->product, (string) $watch->fit_session_key, $userId);
        $decision = app(PurchaseDecision::class)->for($watch->product, $watch->size, $fit);
        $signal = $decision['status'] === 'buy' && (! $watch->budget || $decision['current'] <= (float) $watch->budget);
        return DB::transaction(function () use ($watch, $decision, $signal) {
            $locked = ShoppingWatch::lockForUpdate()->find($watch->id);
            if (! $locked || ! $locked->is_active) return false;
            if (! $signal) { $locked->update(['last_signal' => false]); return false; }
            if ($locked->last_signal || $locked->last_notified_at?->gt(now()->subDay())) return false;
            $generation = $locked->generation + 1;
            WatchNotification::create(['shopping_watch_id' => $locked->id, 'generation' => $generation, 'payload' => [
                'title' => $decision['title'], 'reason' => $decision['reason'], 'price' => $decision['current'],
                'stock_score' => $decision['stock']['score'], 'low_90' => $decision['prices']['low_90'],
                'product_name' => $watch->product->name, 'size' => $watch->size,
                'url' => route('product.show', ['slug' => $watch->product->slug, 'size' => $watch->size]),
            ]]);
            $locked->update(['last_signal' => true, 'generation' => $generation, 'last_notified_at' => now()]);
            return true;
        });
    }
}

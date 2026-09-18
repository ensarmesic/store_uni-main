<?php

namespace App\Console\Commands;

use App\Models\{ShoppingWatch, WatchNotification};
use App\Services\WatchChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache, Mail};

class CheckShoppingWatches extends Command
{
    protected $signature = 'watch:check';
    protected $description = 'Provjerava odluke i dostavlja Watch obavijesti';

    public function handle(WatchChecker $checker): int
    {
        $lock = Cache::lock('watch:check', 3600);
        if (! $lock->get()) return self::SUCCESS;
        try {
            ShoppingWatch::where('is_active', true)->with('product')->chunkById(100, function ($watches) use ($checker) {
                foreach ($watches as $watch) {
                    try { $checker->check($watch); } catch (\Throwable $exception) { report($exception); }
                }
            });
            if (config('mail.default') === 'smtp') {
                WatchNotification::whereNull('emailed_at')->where('attempts', '<', 5)
                    ->where(fn ($query) => $query->whereNull('last_attempt_at')->orWhere('last_attempt_at', '<=', now()->subMinutes(15)))
                    ->whereHas('watch', fn ($query) => $query->where('is_active', true)->whereNotNull('email_verified_at'))
                    ->with('watch')->chunkById(100, function ($notifications) {
                        foreach ($notifications as $notification) {
                            $notification->update(['attempts' => $notification->attempts + 1, 'last_attempt_at' => now()]);
                            try {
                                $p = $notification->payload;
                                Mail::raw($p['product_name'].' / EU '.$p['size']."\n".$p['title']."\n".$p['reason']."\n".$p['price']." KM\n".$p['url']."\nUpravljaj praćenjem: ".route('watch'),
                                    fn ($message) => $message->to($notification->watch->email)->subject('AErchi Watch: vrijedi provjeriti ponudu'));
                                $notification->update(['emailed_at' => now()]);
                            } catch (\Throwable $exception) { report($exception); }
                        }
                    });
            }
            Cache::put('watch:last_checked', now()->toIso8601String(), 86400);
            $this->info('Watch provjera završena.');
        } finally { $lock->release(); }
        return self::SUCCESS;
    }
}

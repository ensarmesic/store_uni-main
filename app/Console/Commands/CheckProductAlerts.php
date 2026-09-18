<?php

namespace App\Console\Commands;

use App\Models\ProductAlert;
use App\Services\ProductAlertStatus;
use Illuminate\Console\Command;

class CheckProductAlerts extends Command
{
    protected $signature = 'alerts:check';

    protected $description = 'Provjerava aktivne price i restock alerte';

    public function handle(ProductAlertStatus $status): int
    {
        $triggered = 0;
        ProductAlert::where('is_active', true)->with('product.offers.variants')->each(function (ProductAlert $alert) use ($status, &$triggered) {
            if ($status->check($alert, $status->currentPrice($alert))) {
                $this->line('Triggered alert #'.$alert->id.' for '.$alert->product->name);
                $triggered++;
            }
        });

        $this->info($triggered.' alert(s) triggered.');
        \Illuminate\Support\Facades\Cache::put('alerts:last_checked', now()->toIso8601String(), 86400);

        return self::SUCCESS;
    }
}

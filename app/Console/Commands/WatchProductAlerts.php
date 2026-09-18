<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WatchProductAlerts extends Command
{
    protected $signature = 'alerts:watch {--interval=300 : Seconds between checks (minimum 30)}';
    protected $description = 'Continuously checks in-app alerts on a local workstation';

    public function handle(): int
    {
        $interval = max(30, (int) $this->option('interval'));
        $this->info('In-app alert worker running. Press Ctrl+C to stop.');
        while (true) {
            try {
                $this->call('alerts:check');
            } catch (\Throwable $exception) {
                report($exception);
                $this->error('Check failed; the next scheduled attempt will retry.');
            }
            sleep($interval);
        }
    }
}

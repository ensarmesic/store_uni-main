<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('products:sync')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('alerts:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('watch:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('lens:index')->dailyAt('03:30')->withoutOverlapping()
    ->when(fn () => is_file(storage_path('app/lens/index.npz')));

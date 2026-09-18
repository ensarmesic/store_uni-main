<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('products:sync')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('alerts:check')->everyFiveMinutes()->withoutOverlapping();

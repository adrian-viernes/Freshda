<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/* Orphaned scan photos (rows removed, file left behind) are reclaimed daily. */
Schedule::command('scans:prune-files')->daily();

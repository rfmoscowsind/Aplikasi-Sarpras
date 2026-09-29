<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('sarpras:about', function () {
    $this->info('Aplikasi Sarpras foundation is ready.');
});

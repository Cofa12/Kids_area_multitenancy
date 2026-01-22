<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:create-tenant {name} {domain} {database}', function ($name, $domain, $database) {
    $this->call(\App\Console\Commands\CreateTenant::class, [
        'name' => $name,
        'domain' => $domain,
        'database' => $database,
    ]);
})->purpose('Create a new tenant with its own database');

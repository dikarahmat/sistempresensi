<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Production: aplikasi ada di folder "presensi" sejajar public_html.
// Lokal: aplikasi ada satu tingkat di atas folder public.
$basePath = is_dir(__DIR__.'/../presensi') ? __DIR__.'/../presensi' : __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());

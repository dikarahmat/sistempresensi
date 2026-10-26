<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Http\Kernel::class)
    ->handle(
        $request = Illuminate\Http\Request::capture()
    )->send();
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — SPA Entry Point
| Semua route dilayani oleh Vue Router, Laravel hanya serve app.blade.php
|--------------------------------------------------------------------------
*/

Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api(?:/|$)).*');

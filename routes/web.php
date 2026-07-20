<?php

use Illuminate\Support\Facades\Route;

/*
|-|--------------------------------------------------------------------------
|-| Web Routes — Redirect Notice
|-| Menampilkan halaman informasi bahwa aplikasi sudah dipindah ke production.
|-|--------------------------------------------------------------------------
*/

Route::get('/{any}', function () {
    return view('redirect-notice');
})->where('any', '^(?!api(?:/|$)).*');

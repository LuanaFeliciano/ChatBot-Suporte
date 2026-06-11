<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/locale/{locale}', LocaleController::class)
    ->middleware(['web', 'auth'])
    ->name('admin.locale.update');

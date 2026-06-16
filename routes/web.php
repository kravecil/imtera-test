<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/login', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('index');
    }

    return view('welcome');
})->name('login');

Route::get('/{a?}/{b?}/{c?}', fn() => view('welcome'))
    ->middleware('auth')
    ->name('index');

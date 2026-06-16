<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', fn() => view('welcome'))->name('login');

Route::get('/{a?}/{b?}/{c?}', fn() => view('welcome'))
    ->middleware('auth')
    ->name('index');

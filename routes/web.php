<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Login/Index');
});

Route::get('/dashboard', function () {
    return Inertia::render('Home/Index');
})->name('dashboard');

Route::get('/register', function () {
    return Inertia::render('Register/Index');
})->name('register');


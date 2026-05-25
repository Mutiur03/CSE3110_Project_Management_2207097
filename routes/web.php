<?php

use App\Http\Controllers\Auth\RegisteredUser;
use App\Http\Controllers\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});
Route::get('/login', [Login::class, 'create'])->name('login');
Route::post('/login', [Login::class, 'store'])->name('login.store');
Route::post('/logout', [Login::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/register', [RegisteredUser::class, 'create'])->name('register');
Route::post('/register', [RegisteredUser::class, 'store'])->name('register.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

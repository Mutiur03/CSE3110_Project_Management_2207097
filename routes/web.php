<?php

use App\Http\Controllers\Auth\RegisteredUser;
use App\Http\Controllers\Auth\Login;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});
Route::get('/login', [Login::class, 'create'])->name('login');
Route::post('/login', [Login::class, 'store'])->name('login.store');
Route::post('/logout', [Login::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/register', [RegisteredUser::class, 'create'])->name('register');
Route::post('/register', [RegisteredUser::class, 'store'])->name('register.store');

Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
});

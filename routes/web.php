<?php

use App\Http\Controllers\Auth\RegisteredUser;
use App\Http\Controllers\Auth\Login;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->middleware('no_back_history');
Route::get('/login', [Login::class, 'create'])->middleware('no_back_history')->name('login');
Route::post('/login', [Login::class, 'store'])->name('login.store');
Route::post('/logout', [Login::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/register', [RegisteredUser::class, 'create'])->middleware('no_back_history')->name('register');
Route::post('/register', [RegisteredUser::class, 'store'])->name('register.store');
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'no_back_history'])->name('dashboard');
Route::middleware(['auth', 'no_back_history'])->group(function () {
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index'])->name('projects.members.index');
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::patch('/projects/{project}/members/{user}', [ProjectMemberController::class, 'update'])->name('projects.members.update');
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    Route::get('/projects/{project}/teams', [TeamController::class, 'index'])->name('projects.teams.index');
    Route::post('/projects/{project}/teams', [TeamController::class, 'store'])->name('projects.teams.store');
    Route::post('/projects/{project}/teams/{team}/members', [TeamController::class, 'addMember'])->name('projects.teams.members.store');
    Route::get('/projects/{project}/issues', [IssueController::class, 'index'])->name('projects.issues.index');
    Route::get('/projects/{project}/issues/create', [IssueController::class, 'create'])->name('projects.issues.create');
    Route::post('/projects/{project}/issues', [IssueController::class, 'store'])->name('projects.issues.store');
    Route::get('/projects/{project}/issues/{issue}', [IssueController::class, 'show'])->name('projects.issues.show');
    Route::patch('/projects/{project}/issues/{issue}', [IssueController::class, 'update'])->name('projects.issues.update');
    Route::get('/projects/{project}/sprints', [SprintController::class, 'index'])->name('projects.sprints.index');
    Route::post('/projects/{project}/sprints', [SprintController::class, 'store'])->name('projects.sprints.store');
    Route::patch('/projects/{project}/sprints/{sprint}', [SprintController::class, 'update'])->name('projects.sprints.update');
    Route::post('/projects/{project}/sprints/{sprint}/issues', [SprintController::class, 'addIssue'])->name('projects.sprints.issues.store');
    Route::delete('/projects/{project}/sprints/{sprint}/issues/{issue}', [SprintController::class, 'removeIssue'])->name('projects.sprints.issues.destroy');
    Route::post('/projects/{project}/sprints/{sprint}/start', [SprintController::class, 'start'])->name('projects.sprints.start');
    Route::post('/projects/{project}/sprints/{sprint}/complete', [SprintController::class, 'complete'])->name('projects.sprints.complete');
    Route::get('/projects/{project}/board', [BoardController::class, 'index'])->name('projects.board.index');
    Route::patch('/projects/{project}/board/issues/{issue}/status', [BoardController::class, 'updateIssueStatus'])->name('projects.board.issues.status');
});

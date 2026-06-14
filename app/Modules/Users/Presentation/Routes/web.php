<?php

use App\Modules\Users\Presentation\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('can:admin.access');
Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('can:admin.access');
Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('can:admin.access');
Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:admin.access');
Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('can:admin.access');

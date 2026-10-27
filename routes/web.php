<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('books', BookController::class);
    Route::resource('students', StudentController::class);
    Route::resource('publishers', PublisherController::class);
    Route::resource('loans', LoanController::class);
    Route::resource('fines', FineController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

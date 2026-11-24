<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\StudentController;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        $today = Carbon::today();
        $openLoans = Loan::query()->whereNull('return_date');
        $issuedLoans = (clone $openLoans)->whereDate('issue_date', '<=', $today);

        return Inertia::render('dashboard', [
            'today' => $today->toDateString(),
            'stats' => [
                'books' => Book::query()->count(),
                'students' => Student::query()->count(),
                'activeLoans' => (clone $issuedLoans)->count(),
                'overdueLoans' => (clone $issuedLoans)->whereDate('due_date', '<', $today)->count(),
                'dueSoon' => (clone $issuedLoans)
                    ->whereBetween('due_date', [$today, $today->copy()->addDays(3)])
                    ->count(),
                'unpaidFines' => (float) Fine::query()->where('payment_status', 'unpaid')->sum('calculated_fine'),
                'unpaidFinesCount' => Fine::query()->where('payment_status', 'unpaid')->count(),
            ],
            'recentLoans' => Loan::query()
                ->with([
                    'student:id,student_number,student_name',
                    'book:id,book_title',
                ])
                ->latest('issue_date')
                ->latest('id')
                ->limit(6)
                ->get(['id', 'student_id', 'book_id', 'issue_date', 'due_date', 'return_date']),
        ]);
    })->name('dashboard');

    Route::resource('books', BookController::class);
    Route::resource('students', StudentController::class);
    Route::resource('publishers', PublisherController::class);
    Route::resource('loans', LoanController::class);
    Route::resource('fines', FineController::class)->except(['create', 'store', 'destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

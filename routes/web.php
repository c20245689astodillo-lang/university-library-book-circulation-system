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
                'books' => Book::query()->whereNull('archived_at')->count(),
                'students' => Student::query()->whereNull('archived_at')->count(),
                'activeLoans' => (clone $issuedLoans)->whereNull('archived_at')->count(),
                'overdueLoans' => (clone $issuedLoans)->whereNull('archived_at')->whereDate('due_date', '<', $today)->count(),
                'dueSoon' => (clone $issuedLoans)
                    ->whereNull('archived_at')
                    ->whereBetween('due_date', [$today, $today->copy()->addDays(3)])
                    ->count(),
                'unpaidFines' => (float) Fine::query()->whereNull('archived_at')->where('payment_status', 'unpaid')->sum('calculated_fine'),
                'unpaidFinesCount' => Fine::query()->whereNull('archived_at')->where('payment_status', 'unpaid')->count(),
            ],
            'recentLoans' => Loan::query()
                ->with([
                    'student:id,student_number,student_name',
                    'book:id,book_title',
                ])
                ->latest('issue_date')
                ->latest('id')
                ->whereNull('archived_at')
                ->limit(6)
                ->get(['id', 'student_id', 'book_id', 'issue_date', 'due_date', 'return_date']),
        ]);
    })->name('dashboard');

    Route::get('archives', [BookController::class, 'archives'])->name('archives.index');
    Route::post('archives/{type}/{id}/archive', [BookController::class, 'archiveRecord'])->name('archives.archive');
    Route::post('archives/{type}/{id}/restore', [BookController::class, 'restoreRecord'])->name('archives.restore');
    Route::resource('books', BookController::class);
    Route::resource('students', StudentController::class);
    Route::resource('publishers', PublisherController::class);
    Route::resource('loans', LoanController::class);
    Route::resource('fines', FineController::class)->except(['create', 'store', 'destroy']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

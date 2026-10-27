<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    private const UNPAID_FINE_LIMIT_CENTS = 50000;

    public function index(): LengthAwarePaginator
    {
        return Loan::query()
            ->select(['id', 'student_id', 'book_id', 'issue_date', 'due_date', 'return_date', 'fine_per_day'])
            ->with([
                'student:id,student_number,student_name',
                'book:id,book_isbn,book_title',
                'fine:id,loan_id,calculated_fine,payment_status',
            ])
            ->latest('issue_date')
            ->paginate(10);
    }

    public function formOptions(): array
    {
        return [
            'students' => Student::query()->orderBy('student_name')->get(['id', 'student_number', 'student_name']),
            'books' => Book::query()->orderBy('book_title')->get(['id', 'book_isbn', 'book_title']),
        ];
    }

    public function create(array $data): Loan
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($data): Loan {
            $studentId = (int) $data['student_id'];
            $this->assertStudentCanBorrow($studentId);

            if (empty($data['return_date'] ?? null)) {
                $this->assertBookAvailable((int) $data['book_id']);
            }

            $loan = Loan::create($data);
            $this->synchronizeCalculatedFine($loan);

            return $loan;
        });
    }

    public function show(Loan $loan): Loan
    {
        return $loan->load(['student', 'book.publisher', 'fine']);
    }

    public function update(Loan $loan, array $data): bool
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($loan, $data): bool {
            $studentId = (int) ($data['student_id'] ?? $loan->student_id);
            $bookId = (int) ($data['book_id'] ?? $loan->book_id);
            $returnDate = array_key_exists('return_date', $data) ? $data['return_date'] : $loan->return_date;

            $reopeningLoan = $loan->return_date !== null && empty($returnDate);
            $changingActiveBook = empty($returnDate) && $bookId !== (int) $loan->book_id;

            if ($studentId !== (int) $loan->student_id || $reopeningLoan || $changingActiveBook) {
                $this->assertStudentCanBorrow($studentId);
            }

            if (empty($returnDate)) {
                $this->assertBookAvailable($bookId, $loan);
            }

            $loan->update($data);
            $loan->refresh();
            $this->synchronizeCalculatedFine($loan);

            return true;
        });
    }

    public function delete(Loan $loan): void
    {
        $loan->delete();
    }

    private function assertStudentCanBorrow(int $studentId): void
    {
        Student::query()->whereKey($studentId)->lockForUpdate()->firstOrFail();

        $unpaidTotal = Fine::query()
            ->where('payment_status', 'unpaid')
            ->whereHas('loan', fn ($query) => $query->where('student_id', $studentId))
            ->sum('calculated_fine');

        if ((int) round((float) $unpaidTotal * 100) > self::UNPAID_FINE_LIMIT_CENTS) {
            throw ValidationException::withMessages([
                'student_id' => 'This student cannot borrow while their unpaid fines exceed ₱500.',
            ]);
        }
    }

    private function assertBookAvailable(int $bookId, ?Loan $except = null): void
    {
        $hasOpenLoan = Loan::query()
            ->where('book_id', $bookId)
            ->whereNull('return_date')
            ->when($except, fn ($query) => $query->where('id', '!=', $except->id))
            ->exists();

        if ($hasOpenLoan) {
            throw ValidationException::withMessages([
                'book_id' => 'This book already has an active loan.',
            ]);
        }
    }

    private function synchronizeCalculatedFine(Loan $loan): void
    {
        if ($loan->return_date === null) {
            $loan->fine()->delete();

            return;
        }

        $dueDate = Carbon::parse($loan->due_date)->startOfDay();
        $returnDate = Carbon::parse($loan->return_date)->startOfDay();
        $overdueDays = max(0, (int) $dueDate->diffInDays($returnDate, false));
        $amount = round($overdueDays * (float) $loan->fine_per_day, 2);
        $fine = $loan->fine()->first();

        if ($amount <= 0) {
            $fine?->delete();

            return;
        }

        if ($fine) {
            $updates = ['calculated_fine' => $amount];

            if ($fine->payment_status === 'paid' && (float) $fine->calculated_fine < $amount) {
                $updates['payment_status'] = 'unpaid';
            }

            $fine->update($updates);

            return;
        }

        $loan->fine()->create([
            'calculated_fine' => $amount,
            'payment_status' => 'unpaid',
        ]);
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        return $data;
    }
}

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

    public function index(?string $search = null): LengthAwarePaginator
    {
        return Loan::query()
            ->select(['id', 'student_id', 'book_id', 'issue_date', 'due_date', 'return_date', 'fine_per_day'])
            ->with([
                'student:id,student_number,student_name',
                'book:id,book_isbn,book_title',
                'fine:id,loan_id,calculated_fine,payment_status',
            ])
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('student', function ($student) use ($search): void {
                        $student->where('student_number', 'like', '%'.$search.'%')
                            ->orWhere('student_name', 'like', '%'.$search.'%');
                    })->orWhereHas('book', function ($book) use ($search): void {
                        $book->where('book_isbn', 'like', '%'.$search.'%')
                            ->orWhere('book_title', 'like', '%'.$search.'%');
                    })->orWhere('issue_date', 'like', '%'.$search.'%')
                        ->orWhere('due_date', 'like', '%'.$search.'%')
                        ->orWhere('return_date', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw('CASE WHEN issue_date = ? THEN 0 ELSE 1 END', [Carbon::today()->toDateString()])
            ->latest('issue_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function formOptions(?Loan $loan = null): array
    {
        $openLoans = Loan::query()->whereNull('return_date');
        $activeLoanCounts = (clone $openLoans)
            ->selectRaw('student_id, COUNT(*) as aggregate')
            ->groupBy('student_id')
            ->pluck('aggregate', 'student_id');
        $reservedBookIds = (clone $openLoans)
            ->when($loan && $loan->return_date === null, fn ($query) => $query->where('id', '!=', $loan->id))
            ->pluck('book_id');

        return [
            'today' => Carbon::today()->toDateString(),
            'students' => Student::query()->orderBy('student_name')->get(['id', 'student_number', 'student_name'])
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'student_name' => $student->student_name,
                    'active_loan_count' => (int) $activeLoanCounts->get($student->id, 0),
                    'disabled' => (int) $activeLoanCounts->get($student->id, 0) >= 3 && $student->id !== $loan?->student_id,
                ])
                ->sort(function (array $left, array $right): int {
                    $studentNumberOrder = strnatcasecmp($left['student_number'], $right['student_number']);

                    return $studentNumberOrder !== 0
                        ? $studentNumberOrder
                        : strnatcasecmp($left['student_name'], $right['student_name']);
                })
                ->values(),
            'books' => Book::query()
                ->where(function ($query) use ($reservedBookIds, $loan): void {
                    $query->whereNotIn('id', $reservedBookIds);

                    if ($loan) {
                        $query->orWhereKey($loan->book_id);
                    }
                })
                ->orderBy('book_title')
                ->get(['id', 'book_isbn', 'book_title']),
        ];
    }

    public function create(array $data): Loan
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($data): Loan {
            $studentId = (int) $data['student_id'];
            $willBeActive = empty($data['return_date'] ?? null);
            $this->assertStudentCanBorrow($studentId, null, $willBeActive);
            $bookId = (int) $data['book_id'];

            if (empty($data['return_date'] ?? null)) {
                $this->assertBookAvailable($bookId);
            } else {
                $this->lockBook($bookId);
            }

            $loan = Loan::create($data);

            if ($loan->return_date !== null) {
                $this->synchronizeCalculatedFine($loan);
            }

            return $loan;
        });
    }

    public function show(Loan $loan): Loan
    {
        return $loan->load([
            'student:id,student_number,student_name',
            'book:id,publisher_id,book_isbn,book_title',
            'book.publisher:id,publisher_name',
            'fine:id,loan_id,calculated_fine,payment_status',
        ]);
    }

    public function update(Loan $loan, array $data): bool
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($loan, $data): bool {
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->getKey());
            $studentId = (int) ($data['student_id'] ?? $loan->student_id);
            $bookId = (int) ($data['book_id'] ?? $loan->book_id);
            $returnDate = array_key_exists('return_date', $data) ? $data['return_date'] : $loan->return_date;

            $reopeningLoan = $loan->return_date !== null && empty($returnDate);
            $changingActiveBook = empty($returnDate) && $bookId !== (int) $loan->book_id;

            if ($studentId !== (int) $loan->student_id || $reopeningLoan || $changingActiveBook) {
                $this->assertStudentCanBorrow($studentId, $loan, empty($returnDate));
            }

            if (empty($returnDate)) {
                $this->assertBookAvailable($bookId, $loan);
            }

            $loan->update($data);
            
            $this->synchronizeCalculatedFine($loan);

            return true;
        });
    }

    public function delete(Loan $loan): void
    {
        DB::transaction(function () use ($loan): void {
            Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail()->delete();
        });
    }

    private function assertStudentCanBorrow(int $studentId, ?Loan $except = null, bool $willBeActive = true): void
    {
        Student::query()->whereKey($studentId)->lockForUpdate()->firstOrFail();

        if ($willBeActive) {
            $activeLoanCount = Loan::query()
                ->where('student_id', $studentId)
                ->whereNull('return_date')
                ->when($except, fn ($query) => $query->where('id', '!=', $except->id))
                ->count();

            if ($activeLoanCount >= 3) {
                throw ValidationException::withMessages([
                    'student_id' => 'A student can have no more than 3 active loans at a time.',
                ]);
            }
        }

        $unpaidTotal = Fine::query()
            ->join('loans', 'loans.id', '=', 'fines.loan_id')
            ->where('fines.payment_status', 'unpaid')
            ->where('loans.student_id', $studentId)
            ->sum('fines.calculated_fine');

        if ((int) round((float) $unpaidTotal * 100) > self::UNPAID_FINE_LIMIT_CENTS) {
            throw ValidationException::withMessages([
                'student_id' => 'This student cannot borrow while their unpaid fines exceed PHP 500.',
            ]);
        }

    }

    private function assertBookAvailable(int $bookId, ?Loan $except = null): void
    {
        $this->lockBook($bookId);

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

    private function lockBook(int $bookId): void
    {
        Book::query()->whereKey($bookId)->lockForUpdate()->firstOrFail();
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
        

        if ($amount <= 0) {
            $loan->fine()->delete();

            return;
        }

        $fine = $loan->fine()->first();

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

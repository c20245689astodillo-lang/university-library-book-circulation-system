<?php

namespace App\Services;

use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FineService
{
    public function index(?string $search = null): LengthAwarePaginator
    {
        return Fine::query()
            ->select(['id', 'loan_id', 'calculated_fine', 'payment_status'])
            ->whereNull('archived_at')
            ->with([
                'loan:id,student_id,book_id,issue_date,due_date,return_date',
                'loan.student:id,student_number,student_name',
                'loan.book:id,book_isbn,book_title',
            ])
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('payment_status', 'like', '%'.$search.'%')
                        ->orWhere('calculated_fine', 'like', '%'.$search.'%')
                        ->orWhereHas('loan.student', function ($student) use ($search): void {
                            $student->where('student_number', 'like', '%'.$search.'%')
                                ->orWhere('student_name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('loan.book', function ($book) use ($search): void {
                            $book->where('book_isbn', 'like', '%'.$search.'%')
                                ->orWhere('book_title', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function show(Fine $fine): Fine
    {
        return $fine->load([
            'loan:id,student_id,book_id',
            'loan.student:id,student_number,student_name',
            'loan.book:id,book_isbn,book_title',
        ]);
    }

    public function update(Fine $fine, array $data): bool
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($fine, $data): bool {
            $loan = Loan::query()->whereKey($fine->loan_id)->lockForUpdate()->firstOrFail();
            $loan->student()->lockForUpdate()->firstOrFail();
            $lockedFine = Fine::query()->lockForUpdate()->findOrFail($fine->getKey());

            return $lockedFine->update(['payment_status' => $data['payment_status']]);
        });
    }

    public function archive(Fine $fine): void
    {
        DB::transaction(function () use ($fine): void {
            $lockedFine = Fine::query()->lockForUpdate()->findOrFail($fine->getKey());

            if ($lockedFine->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'fine' => 'An unpaid fine cannot be archived.',
                ]);
            }

            $lockedFine->forceFill(['archived_at' => now()])->save();
        });
    }

    public function restore(Fine $fine): void
    {
        DB::transaction(function () use ($fine): void {
            $lockedFine = Fine::query()->lockForUpdate()->findOrFail($fine->getKey());
            $lockedFine->forceFill(['archived_at' => null])->save();
        });
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        if (isset($data['payment_status'])) {
            $data['payment_status'] = strtolower($data['payment_status']);
        }

        return $data;
    }
}

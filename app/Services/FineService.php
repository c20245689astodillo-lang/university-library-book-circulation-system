<?php

namespace App\Services;

use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FineService
{
    public function index(): LengthAwarePaginator
    {
        return Fine::query()
            ->select(['id', 'loan_id', 'calculated_fine', 'payment_status'])
            ->with([
                'loan:id,student_id,book_id,issue_date,due_date,return_date',
                'loan.student:id,student_number,student_name',
                'loan.book:id,book_isbn,book_title',
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate(10);
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

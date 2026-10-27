<?php

namespace App\Services;

use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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
            ->latest()
            ->paginate(10);
    }

    public function availableLoans(?Fine $fine = null): Collection
    {
        return Loan::query()
            ->with([
                'student:id,student_number,student_name',
                'book:id,book_isbn,book_title',
            ])
            ->where(function ($query) use ($fine) {
                $query->whereDoesntHave('fine');

                if ($fine !== null) {
                    $query->orWhereKey($fine->loan_id);
                }
            })
            ->orderByDesc('issue_date')
            ->get(['id', 'student_id', 'book_id', 'issue_date', 'due_date', 'return_date']);
    }

    public function create(array $data): Fine
    {
        return Fine::create($this->sanitize($data));
    }

    public function show(Fine $fine): Fine
    {
        return $fine->load([
            'loan.student:id,student_number,student_name',
            'loan.book:id,book_isbn,book_title',
        ]);
    }

    public function update(Fine $fine, array $data): bool
    {
        return $fine->update($this->sanitize($data));
    }

    public function delete(Fine $fine): void
    {
        $fine->delete();
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

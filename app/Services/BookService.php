<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function index(): LengthAwarePaginator
    {
        return Book::query()
            ->select(['id', 'publisher_id', 'book_isbn', 'book_title', 'author_name'])
            ->with('publisher:id,publisher_name')
            ->latest('created_at')
            ->latest('id')
            ->paginate(10);
    }

    public function publishers(): Collection
    {
        return Publisher::query()->orderBy('publisher_name')->get(['id', 'publisher_name']);
    }

    public function create(array $data): Book
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($data): Book {
            Publisher::query()->whereKey($data['publisher_id'])->lockForUpdate()->firstOrFail();

            return Book::create($data);
        });
    }

    public function show(Book $book): Book
    {
        return $book->load('publisher:id,publisher_name');
    }

    public function hasLoans(Book $book): bool
    {
        return $book->loans()->exists();
    }

    public function update(Book $book, array $data): bool
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($book, $data): bool {
            $lockedBook = Book::query()->lockForUpdate()->findOrFail($book->getKey());
            $publisherId = $data['publisher_id'] ?? $lockedBook->publisher_id;
            Publisher::query()->whereKey($publisherId)->lockForUpdate()->firstOrFail();

            return $lockedBook->update($data);
        });
    }

    public function delete(Book $book): void
    {
        DB::transaction(function () use ($book): void {
            $lockedBook = Book::query()->lockForUpdate()->findOrFail($book->getKey());

            if ($this->hasLoans($lockedBook)) {
                throw ValidationException::withMessages([
                    'book' => 'This book cannot be deleted because it has loan records.',
                ]);
            }

            $lockedBook->delete();
        });
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim(strip_tags($value));
            }
        }

        if (isset($data['book_title'])) {
            $data['book_title'] = mb_convert_case($data['book_title'], MB_CASE_TITLE, 'UTF-8');
        }

        return $data;
    }
}

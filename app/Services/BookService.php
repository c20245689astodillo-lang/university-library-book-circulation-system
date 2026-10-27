<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function index(): LengthAwarePaginator
    {
        return Book::query()
            ->select(['id', 'publisher_id', 'book_isbn', 'book_title', 'author_name'])
            ->with('publisher:id,publisher_name')
            ->latest()
            ->paginate(10);
    }

    public function publishers(): Collection
    {
        return Publisher::query()->orderBy('publisher_name')->get(['id', 'publisher_name']);
    }

    public function create(array $data): Book
    {
        return Book::create($this->sanitize($data));
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
        return $book->update($this->sanitize($data));
    }

    public function delete(Book $book): void
    {
        if ($this->hasLoans($book)) {
            throw ValidationException::withMessages([
                'book' => 'This book cannot be deleted because it has loan records.',
            ]);
        }

        $book->delete();
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

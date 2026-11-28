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
    public function index(?string $search = null): LengthAwarePaginator
    {
        return Book::query()
            ->select(['id', 'publisher_id', 'book_isbn', 'book_title', 'author_name'])
            ->with('publisher:id,publisher_name')
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('book_isbn', 'like', '%'.$search.'%')
                        ->orWhere('book_title', 'like', '%'.$search.'%')
                        ->orWhere('author_name', 'like', '%'.$search.'%')
                        ->orWhereHas('publisher', fn ($publisher) => $publisher->where('publisher_name', 'like', '%'.$search.'%'));
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function publishers(): Collection
    {
        return Publisher::query()->orderBy('publisher_name')->get(['id', 'publisher_name']);
    }

    public function nextAvailableBookCodes(int $limit = 5): array
    {
        $usedCodes = Book::query()->pluck('book_isbn')->all();
        $usedCodes = array_fill_keys($usedCodes, true);
        $lastNumber = 100;

        foreach (array_keys($usedCodes) as $usedCode) {
            if (preg_match('/^100-(\d+)$/', $usedCode, $matches) !== 1) {
                continue;
            }

            $lastNumber = max($lastNumber, (int) $matches[1]);
        }

        $codes = [];

        for ($number = $lastNumber + 1; $number <= 9999 && count($codes) < $limit; $number++) {
            $code = '100-'.$number;

            if (isset($usedCodes[$code])) {
                continue;
            }

            $codes[] = $code;
        }

        if ($codes !== []) {
            return $codes;
        }

        throw ValidationException::withMessages([
            'book_isbn' => 'All automatically generated book codes are in use.',
        ]);
    }

    public function nextAvailableBookCode(): string
    {
        return $this->nextAvailableBookCodes(1)[0];
    }

    public function create(array $data): Book
    {
        $data = $this->sanitize($data);
        $selectedCode = $data['book_isbn'] ?? null;
        unset($data['book_isbn']);

        return DB::transaction(function () use ($data, $selectedCode): Book {
            // Lock one shared row so simultaneous creates receive different codes.
            Publisher::query()->orderBy('id')->lockForUpdate()->firstOrFail();
            Publisher::query()->whereKey($data['publisher_id'])->lockForUpdate()->firstOrFail();

            $availableCodes = $this->nextAvailableBookCodes();

            if ($selectedCode !== null && $selectedCode !== $availableCodes[0]) {
                throw ValidationException::withMessages([
                    'book_isbn' => 'Choose the next book code in order. Refresh the page to get the current code.',
                ]);
            }

            $data['book_isbn'] = $selectedCode ?? $availableCodes[0];

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

            if ((int) $publisherId !== (int) $lockedBook->publisher_id) {
                Publisher::query()->whereKey($publisherId)->lockForUpdate()->firstOrFail();
            }

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

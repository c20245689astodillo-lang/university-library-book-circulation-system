<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Publisher;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function __construct(
        private StudentService $students,
        private PublisherService $publishers,
        private LoanService $loans,
        private FineService $fines,
    ) {
    }

    public function index(?string $search = null): LengthAwarePaginator
    {
        return Book::query()
            ->select(['id', 'publisher_id', 'book_isbn', 'book_title', 'author_name'])
            ->with('publisher:id,publisher_name')
            ->withCount(['loans as active_loans_count' => fn ($query) => $query->whereNull('return_date')])
            ->whereNull('archived_at')
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

    public function archivedRecords(string $type, ?string $search = null): LengthAwarePaginator
    {
        $search = $search !== null && $search !== '' ? $search : null;

        return match ($type) {
            'books' => Book::query()
                ->with('publisher:id,publisher_name')
                ->whereNotNull('archived_at')
                ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('book_isbn', 'like', '%'.$search.'%')
                        ->orWhere('book_title', 'like', '%'.$search.'%')
                        ->orWhere('author_name', 'like', '%'.$search.'%')
                        ->orWhereHas('publisher', fn ($publisher) => $publisher->where('publisher_name', 'like', '%'.$search.'%'));
                }))
                ->latest('archived_at')->latest('id')->paginate(10)->through(fn (Book $book) => [
                    'id' => $book->id,
                    'type' => 'books',
                    'name' => $book->book_title,
                    'details' => trim($book->book_isbn.' | '.$book->author_name.' | '.($book->publisher?->publisher_name ?? '')),
                    'archived_at' => $book->archived_at?->toDateString(),
                ]),
            'students' => Student::query()
                ->whereNotNull('archived_at')
                ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('student_number', 'like', '%'.$search.'%')
                        ->orWhere('student_name', 'like', '%'.$search.'%')
                        ->orWhere('student_email', 'like', '%'.$search.'%');
                }))
                ->latest('archived_at')->latest('id')->paginate(10)->through(fn (Student $student) => [
                    'id' => $student->id,
                    'type' => 'students',
                    'name' => $student->student_name,
                    'details' => $student->student_number.' | '.$student->student_email,
                    'archived_at' => $student->archived_at?->toDateString(),
                ]),
            'publishers' => Publisher::query()
                ->whereNotNull('archived_at')
                ->when($search, fn ($query) => $query->where('publisher_name', 'like', '%'.$search.'%'))
                ->latest('archived_at')->latest('id')->paginate(10)->through(fn (Publisher $publisher) => [
                    'id' => $publisher->id,
                    'type' => 'publishers',
                    'name' => $publisher->publisher_name,
                    'details' => 'Publisher',
                    'archived_at' => $publisher->archived_at?->toDateString(),
                ]),
            'loans' => Loan::query()
                ->with(['student:id,student_number,student_name', 'book:id,book_isbn,book_title'])
                ->whereNotNull('archived_at')
                ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->whereHas('student', fn ($student) => $student->where('student_number', 'like', '%'.$search.'%')->orWhere('student_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('book', fn ($book) => $book->where('book_isbn', 'like', '%'.$search.'%')->orWhere('book_title', 'like', '%'.$search.'%'));
                }))
                ->latest('archived_at')->latest('id')->paginate(10)->through(fn (Loan $loan) => [
                    'id' => $loan->id,
                    'type' => 'loans',
                    'name' => $loan->book?->book_title ?? 'Unknown book',
                    'details' => trim(($loan->student?->student_name ?? 'Unknown student').' ('.$loan->student?->student_number.') | Issued '.$loan->issue_date?->toDateString().' | Returned '.$loan->return_date?->toDateString()),
                    'archived_at' => $loan->archived_at?->toDateString(),
                ]),
            'fines' => Fine::query()
                ->with(['loan.student:id,student_number,student_name', 'loan.book:id,book_isbn,book_title'])
                ->whereNotNull('archived_at')
                ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('payment_status', 'like', '%'.$search.'%')
                        ->orWhere('calculated_fine', 'like', '%'.$search.'%')
                        ->orWhereHas('loan.student', fn ($student) => $student->where('student_number', 'like', '%'.$search.'%')->orWhere('student_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('loan.book', fn ($book) => $book->where('book_isbn', 'like', '%'.$search.'%')->orWhere('book_title', 'like', '%'.$search.'%'));
                }))
                ->latest('archived_at')->latest('id')->paginate(10)->through(fn (Fine $fine) => [
                    'id' => $fine->id,
                    'type' => 'fines',
                    'name' => 'PHP '.number_format((float) $fine->calculated_fine, 2).' fine',
                    'details' => trim(($fine->loan?->student?->student_name ?? 'Unknown student').' | '.($fine->loan?->book?->book_title ?? 'Unknown book').' | '.ucfirst($fine->payment_status)),
                    'archived_at' => $fine->archived_at?->toDateString(),
                ]),
            default => throw ValidationException::withMessages(['type' => 'Choose a valid archive category.']),
        };
    }

    public function archiveRecord(string $type, int $id): void
    {
        $record = $this->findArchiveRecord($type, $id);

        switch ($type) {
            case 'books': $this->archive($record); break;
            case 'students': $this->students->archive($record); break;
            case 'publishers': $this->publishers->archive($record); break;
            case 'loans': $this->loans->archive($record); break;
            case 'fines': $this->fines->archive($record); break;
        }
    }

    public function restoreRecord(string $type, int $id): void
    {
        $record = $this->findArchiveRecord($type, $id);

        switch ($type) {
            case 'books': $this->restore($record); break;
            case 'students': $this->students->restore($record); break;
            case 'publishers': $this->publishers->restore($record); break;
            case 'loans': $this->loans->restore($record); break;
            case 'fines': $this->fines->restore($record); break;
        }
    }

    private function findArchiveRecord(string $type, int $id): Model
    {
        $model = match ($type) {
            'books' => Book::class,
            'students' => Student::class,
            'publishers' => Publisher::class,
            'loans' => Loan::class,
            'fines' => Fine::class,
            default => abort(404),
        };

        return $model::query()->findOrFail($id);
    }

    public function publishers(?Publisher $currentPublisher = null): Collection
    {
        return Publisher::query()
            ->whereNull('archived_at')
            ->when($currentPublisher, fn ($query) => $query->orWhere('id', $currentPublisher->getKey()))
            ->orderBy('publisher_name')
            ->get(['id', 'publisher_name']);
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
            $publisher = Publisher::query()->whereKey($data['publisher_id'])->lockForUpdate()->firstOrFail();

            if ($publisher->archived_at !== null) {
                throw ValidationException::withMessages([
                    'publisher_id' => 'Choose an active publisher for a new book.',
                ]);
            }

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

    public function archive(Book $book): void
    {
        DB::transaction(function () use ($book): void {
            $lockedBook = Book::query()->lockForUpdate()->findOrFail($book->getKey());

            if ($lockedBook->archived_at !== null) {
                return;
            }

            $hasOpenLoan = $lockedBook->loans()->whereNull('return_date')->exists();

            if ($hasOpenLoan) {
                throw ValidationException::withMessages([
                    'book' => 'A book with an active loan or reservation cannot be archived.',
                ]);
            }

            $lockedBook->forceFill(['archived_at' => now()])->save();
        });
    }

    public function restore(Book $book): void
    {
        DB::transaction(function () use ($book): void {
            $lockedBook = Book::query()->lockForUpdate()->findOrFail($book->getKey());
            $lockedBook->forceFill(['archived_at' => null])->save();
        });
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
                $publisher = Publisher::query()->whereKey($publisherId)->lockForUpdate()->firstOrFail();

                if ($publisher->archived_at !== null) {
                    throw ValidationException::withMessages([
                        'publisher_id' => 'Choose an active publisher for this book.',
                    ]);
                }
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

<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __construct(private BookService $service)
    {
    }

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = isset($validated['search']) ? trim(strip_tags($validated['search'])) : null;

        return Inertia::render('books/index', [
            'books' => $this->service->index($search),
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function archives(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:books,students,publishers,loans,fines'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $type = $validated['type'] ?? 'books';
        $search = isset($validated['search']) ? trim(strip_tags($validated['search'])) : null;

        return Inertia::render('books/index', [
            'records' => $this->service->archivedRecords($type, $search),
            'archiveType' => $type,
            'isArchives' => true,
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function archiveRecord(string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['books', 'students', 'publishers', 'loans', 'fines'], true), 404);
        $this->service->archiveRecord($type, $id);

        return redirect()->route('archives.index', ['type' => $type])->with('success', ucfirst(rtrim($type, 's')).' archived.');
    }

    public function restoreRecord(string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['books', 'students', 'publishers', 'loans', 'fines'], true), 404);
        $this->service->restoreRecord($type, $id);

        return redirect()->route('archives.index', ['type' => $type])->with('success', ucfirst(rtrim($type, 's')).' restored.');
    }

    public function create(): Response
    {
        $bookCodeOptions = $this->service->nextAvailableBookCodes(5);

        return Inertia::render('books/form', [
            'publishers' => $this->service->publishers(),
            'suggestedBookCode' => $bookCodeOptions[0],
            'bookCodeOptions' => $bookCodeOptions,
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('books.index')->with('success', 'Book added.');
    }

    public function show(Book $book): Response
    {
        return Inertia::render('books/show', ['book' => $this->service->show($book)]);
    }

    public function edit(Book $book): Response
    {
        return Inertia::render('books/form', [
            'book' => $book,
            'publishers' => $this->service->publishers($book->publisher),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->service->update($book, $request->validated());

        return redirect()->route('books.index')->with('success', 'Book updated.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->service->delete($book);

        return redirect()->route('books.index')->with('success', 'Book deleted.');
    }

}

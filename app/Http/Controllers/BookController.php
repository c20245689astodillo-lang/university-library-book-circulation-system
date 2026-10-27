<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __construct(private BookService $service)
    {
    }

    public function index(): Response
    {
        $books = $this->service->index();

        return Inertia::render('books/index', [
            'books' => $books,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('books/form', [
            'publishers' => $this->service->publishers(),
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
            'publishers' => $this->service->publishers(),
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

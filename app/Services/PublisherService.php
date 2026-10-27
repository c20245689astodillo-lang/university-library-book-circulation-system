<?php

namespace App\Services;

use App\Models\Publisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class PublisherService
{
    public function index(): LengthAwarePaginator
    {
        return Publisher::query()
            ->select(['id', 'publisher_name'])
            ->withCount('books')
            ->orderBy('publisher_name')
            ->paginate(10);
    }

    public function all(): Collection
    {
        return Publisher::query()->orderBy('publisher_name')->get(['id', 'publisher_name']);
    }

    public function create(array $data): Publisher
    {
        return Publisher::create($this->sanitize($data));
    }

    public function show(Publisher $publisher): Publisher
    {
        return $publisher->load('books:id,publisher_id,book_isbn,book_title,author_name');
    }

    public function update(Publisher $publisher, array $data): bool
    {
        return $publisher->update($this->sanitize($data));
    }

    public function hasBooks(Publisher $publisher): bool
    {
        return $publisher->books()->exists();
    }

    public function delete(Publisher $publisher): void
    {
        if ($this->hasBooks($publisher)) {
            throw ValidationException::withMessages([
                'publisher' => 'This publisher cannot be deleted while it has books.',
            ]);
        }

        $publisher->delete();
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

<?php

namespace App\Services;

use App\Models\Publisher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublisherService
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Publisher::query()
            ->select(['id', 'publisher_name'])
            ->whereNull('archived_at')
            ->withCount('books')
            ->search($search)
            ->orderBy('publisher_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function all(): Collection
    {
        return Publisher::query()->whereNull('archived_at')->orderBy('publisher_name')->get(['id', 'publisher_name']);
    }

    public function create(array $data): Publisher
    {
        $data = $this->sanitize($data);

        return DB::transaction(fn (): Publisher => Publisher::create($data));
    }

    public function show(Publisher $publisher): Publisher
    {
        return $publisher->load('books:id,publisher_id,book_isbn,book_title,author_name');
    }

    public function update(Publisher $publisher, array $data): bool
    {
        $data = $this->sanitize($data);

        return DB::transaction(function () use ($publisher, $data): bool {
            $lockedPublisher = Publisher::query()->lockForUpdate()->findOrFail($publisher->getKey());

            return $lockedPublisher->update($data);
        });
    }

    public function hasBooks(Publisher $publisher): bool
    {
        return $publisher->books()->exists();
    }

    public function archive(Publisher $publisher): void
    {
        DB::transaction(function () use ($publisher): void {
            $lockedPublisher = Publisher::query()->lockForUpdate()->findOrFail($publisher->getKey());
            $lockedPublisher->forceFill(['archived_at' => now()])->save();
        });
    }

    public function restore(Publisher $publisher): void
    {
        DB::transaction(function () use ($publisher): void {
            $lockedPublisher = Publisher::query()->lockForUpdate()->findOrFail($publisher->getKey());
            $lockedPublisher->forceFill(['archived_at' => null])->save();
        });
    }

    public function delete(Publisher $publisher): void
    {
        DB::transaction(function () use ($publisher): void {
            $lockedPublisher = Publisher::query()->lockForUpdate()->findOrFail($publisher->getKey());

            if ($this->hasBooks($lockedPublisher)) {
                throw ValidationException::withMessages([
                    'publisher' => 'This publisher cannot be deleted while it has books.',
                ]);
            }

            $lockedPublisher->delete();
        });
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

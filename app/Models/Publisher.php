<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends Model
{
    protected $fillable = ['publisher_name'];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
            $query->where('publisher_name', 'like', '%'.$search.'%');
        });
    }
}

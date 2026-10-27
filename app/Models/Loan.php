<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
     protected $fillable = [
        'student_id',
        'book_id',
        'issue_date',
        'due_date',
        'return_date',
        'fine_per_day',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'due_date'     => 'date',
        'return_date'  => 'date',
        'fine_per_day' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function fine(): HasOne
    {
        return $this->hasOne(Fine::class);
    }
}

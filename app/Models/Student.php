<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Student extends Model
{
    protected $fillable = ['student_number', 'student_name', 'student_email'];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function fines(): HasManyThrough
    {
        return $this->hasManyThrough(Fine::class, Loan::class);
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'book_id' => ['required', 'integer', Rule::exists('books', 'id')],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'fine_per_day' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Choose a student for this loan.',
            'student_id.exists' => 'Choose a student that exists.',
            'book_id.required' => 'Choose a book for this loan.',
            'book_id.exists' => 'Choose a book that exists.',
            'issue_date.required' => 'Enter the issue date.',
            'due_date.required' => 'Enter the due date.',
            'due_date.after_or_equal' => 'The due date cannot be before the issue date.',
            'return_date.after_or_equal' => 'The return date cannot be before the issue date.',
            'fine_per_day.min' => 'The daily fine cannot be negative.',
        ];
    }
}

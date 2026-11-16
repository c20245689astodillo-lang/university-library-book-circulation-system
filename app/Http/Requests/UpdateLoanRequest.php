<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $loan = $this->route('loan');
        $originalIssueDate = $loan instanceof Loan ? $loan->getRawOriginal('issue_date') : null;

        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'book_id' => ['required', 'integer', Rule::exists('books', 'id')],
            'issue_date' => ['required', 'date_format:Y-m-d', Rule::in([$originalIssueDate])],
            'due_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:issue_date',
                $this->withinFiveDaysOf(
                    is_string($originalIssueDate) ? $originalIssueDate : $this->input('issue_date'),
                    'The due date cannot be more than five days after the issue date.',
                ),
            ],
            'return_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:issue_date',
                $this->withinFiveDaysOf($this->input('due_date'), 'The return date cannot be more than five days after the due date.'),
            ],
            'fine_per_day' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
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
            'issue_date.date_format' => 'Enter the issue date in YYYY-MM-DD format.',
            'issue_date.in' => 'The issue date cannot be changed after the loan is created.',
            'due_date.required' => 'Enter the due date.',
            'due_date.date_format' => 'Enter the due date in YYYY-MM-DD format.',
            'due_date.after_or_equal' => 'The due date cannot be before the issue date.',
            'return_date.date_format' => 'Enter the return date in YYYY-MM-DD format.',
            'return_date.after_or_equal' => 'The return date cannot be before the issue date.',
            'fine_per_day.decimal' => 'The daily fine can have no more than two decimal places.',
            'fine_per_day.min' => 'The daily fine cannot be negative.',
        ];
    }

    private function withinFiveDaysOf(mixed $anchorDate, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($anchorDate, $message): void {
            if (! is_string($anchorDate) || ! is_string($value)) {
                return;
            }

            try {
                $anchor = CarbonImmutable::createFromFormat('!Y-m-d', $anchorDate);
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
            } catch (Throwable) {
                return;
            }

            if ($anchor && $date && $date->greaterThan($anchor->addDays(5))) {
                $fail($message);
            }
        };
    }
}

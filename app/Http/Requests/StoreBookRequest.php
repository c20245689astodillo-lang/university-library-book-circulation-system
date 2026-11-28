<?php

namespace App\Http\Requests;

use App\Models\Book;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['book_title', 'author_name'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized = trim(strip_tags($value));

                if ($field === 'book_title') {
                    $normalized = mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
                }

                $this->merge([$field => $normalized]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'publisher_id' => ['required', 'integer', Rule::exists('publishers', 'id')],
            'book_isbn' => ['required', 'string', 'max:20', 'regex:/^100-\d{3,4}$/', Rule::unique('books', 'book_isbn')],
            'book_title' => ['required', 'string', 'max:255', $this->bookTitleRule(), $this->uniqueTitleAuthorPairRule()],
            'author_name' => ['required', 'string', 'max:255', $this->authorNameRule()],
        ];
    }

    public function messages(): array
    {
        return [
            'publisher_id.required' => 'Choose a publisher for the book.',
            'publisher_id.exists' => 'Choose a publisher that exists.',
            'book_isbn.required' => 'Choose an automatically generated book code.',
            'book_isbn.string' => 'The book code must be text.',
            'book_isbn.max' => 'The book code may not be longer than 20 characters.',
            'book_isbn.regex' => 'Choose one of the generated book codes.',
            'book_isbn.unique' => 'That book code is already assigned. Refresh the page and choose another option.',
            'book_title.required' => 'Enter the book title.',
            'book_title.string' => 'The book title must be text.',
            'book_title.max' => 'The book title may not be longer than 255 characters.',
            'author_name.required' => 'Enter the author name.',
            'author_name.string' => 'The author name must be text.',
            'author_name.max' => 'The author name may not be longer than 255 characters.',
        ];
    }

    private function authorNameRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                return;
            }

            if (preg_match('~^[\p{L}\p{M}]+(?:[ .,\x27\x{2019}\-]+[\p{L}\p{M}]+)*\.?$~u', $value) !== 1) {
                $fail('Enter an author name using letters, spaces, and common name punctuation only.');

                return;
            }

            if (preg_match('~(\p{L})\1{4,}~iu', $value) === 1) {
                $fail('The author name cannot contain five or more repeated letters.');

                return;
            }

            if ($this->hasSuspiciousLetterSequence($value)) {
                $fail('The author name contains an unusual letter sequence; check it for typing errors.');
            }
        };
    }

    private function bookTitleRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                return;
            }

            if (preg_match('~^[\p{L}\p{M}\p{N}\s.,:;!?&\x27\x{2019}"()\[\]/_#@+\-]+$~u', $value) !== 1) {
                $fail('Use letters, numbers, spaces, and common title punctuation only.');

                return;
            }

            if (preg_match('~(\p{L})\1{4,}~iu', $value) === 1) {
                $fail('The title cannot contain five or more repeated letters.');

                return;
            }

            if (preg_match('~(\p{N})\1{2,}~u', $value) === 1) {
                $fail('The title cannot repeat the same digit three or more times in a row.');

                return;
            }

            if ($this->hasLongNumber($value)) {
                $fail('Numbers in the title may not contain more than three digits in a row.');

                return;
            }

            if ($this->hasSuspiciousLetterSequence($value)) {
                $fail('The title contains an unusual letter sequence; check it for typing errors.');
            }
        };
    }

    private function uniqueTitleAuthorPairRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $title = $this->input('book_title');
            $author = $this->input('author_name');

            if (! is_string($title) || ! is_string($author)) {
                return;
            }

            $exists = Book::query()
                ->whereRaw('LOWER(book_title) = ?', [mb_strtolower($title, 'UTF-8')])
                ->whereRaw('LOWER(author_name) = ?', [mb_strtolower($author, 'UTF-8')])
                ->exists();

            if ($exists) {
                $fail('This book already exists in the catalog.');
            }
        };
    }

    private function hasSuspiciousLetterSequence(string $value): bool
    {
        $words = preg_split('~[^\p{L}\p{M}]+~u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            if (preg_match('~^[\p{Latin}\p{M}]+$~u', $word) !== 1) {
                continue;
            }

            $hasVowel = preg_match('~[aeiouy]~i', $word) === 1;

            if (preg_match('~^[A-Za-z]{5,}$~', $word) === 1 && ! $hasVowel) {
                return true;
            }

            if (preg_match('~[bcdfghjklmnpqrstvwxz]{6,}~i', $word) === 1) {
                return true;
            }
        }

        return false;
    }

    private function hasLongNumber(string $value): bool
    {
        return preg_match('~\p{N}{4,}~u', $value) === 1;
    }

}

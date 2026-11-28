<?php

namespace App\Http\Requests;

use App\Models\Book;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['book_isbn', 'book_title', 'author_name'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized = trim(strip_tags($value));

                if ($field === 'book_title') {
                    $normalized = mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
                }

                $this->merge([$field => $field === 'book_isbn' ? strtoupper($normalized) : $normalized]);
            }
        }
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'publisher_id' => ['required', 'integer', Rule::exists('publishers', 'id')],
            'book_isbn' => [
                'required', 'string', 'max:20', 'regex:/^(?:[0-9]{1,3}-[0-9]{1,4}|[0-9]+(?:-[0-9]+)*(?:-?X)?)$/i',
                $this->isbnRule(),
                Rule::unique('books', 'book_isbn')->ignore($book?->id),
            ],
            'book_title' => ['required', 'string', 'max:255', $this->bookTitleRule(), $this->uniqueTitleRule($book instanceof Book ? $book : null)],
            'author_name' => ['required', 'string', 'max:255', $this->authorNameRule(), $this->uniqueAuthorRule($book instanceof Book ? $book : null)],
        ];
    }

    public function messages(): array
    {
        return [
            'publisher_id.required' => 'Choose a publisher for the book.',
            'publisher_id.exists' => 'Choose a publisher that exists.',
            'book_isbn.required' => 'Enter the book ISBN.',
            'book_isbn.string' => 'The ISBN must be text.',
            'book_isbn.max' => 'The ISBN may not be longer than 20 characters.',
            'book_isbn.regex' => 'Enter a project code with 1 to 3 digits before the hyphen and 1 to 4 after, such as 789-90 or 568-0012, or a valid ISBN.',
            'book_isbn.unique' => 'That ISBN is already assigned to another book.',
            'book_title.required' => 'Enter the book title.',
            'book_title.string' => 'The book title must be text.',
            'book_title.max' => 'The book title may not be longer than 255 characters.',
            'author_name.required' => 'Enter the author name.',
            'author_name.string' => 'The author name must be text.',
            'author_name.max' => 'The author name may not be longer than 255 characters.',
        ];
    }

    private function isbnRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || preg_match('/^\d{1,3}-\d{1,4}$/', $value) === 1) {
                return;
            }

            $isbn = strtoupper(str_replace('-', '', $value));

            if (preg_match('/^\d{9}[\dX]$/', $isbn) === 1) {
                $sum = 0;

                for ($index = 0; $index < 10; $index++) {
                    $digit = $isbn[$index] === 'X' ? 10 : (int) $isbn[$index];
                    $sum += $digit * (10 - $index);
                }

                if ($sum % 11 === 0) {
                    return;
                }
            } elseif (preg_match('/^(978|979)\d{10}$/', $isbn) === 1) {
                $sum = 0;

                for ($index = 0; $index < 12; $index++) {
                    $sum += (int) $isbn[$index] * ($index % 2 === 0 ? 1 : 3);
                }

                if ((10 - ($sum % 10)) % 10 === (int) $isbn[12]) {
                    return;
                }
            }

            $fail('Enter a project code with 1 to 3 digits before the hyphen and 1 to 4 after, such as 789-90 or 568-0012, or a valid ISBN-10/13 with a correct check digit.');
        };
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

    private function uniqueTitleRule(?Book $book): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($book): void {
            if (! is_string($value)) {
                return;
            }

            $normalizedTitle = mb_strtolower($value, 'UTF-8');

            $query = Book::query()->whereRaw('LOWER(book_title) = ?', [$normalizedTitle]);

            if ($book) {
                $query->where('id', '!=', $book->getKey());
            }

            if ($query->exists()) {
                $fail('The title "'.$value.'" already exists. Enter a different title.');
            }
        };
    }

    private function uniqueAuthorRule(?Book $book): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($book): void {
            if (! is_string($value)) {
                return;
            }

            $normalizedAuthor = mb_strtolower($value, 'UTF-8');

            $query = Book::query()->whereRaw('LOWER(author_name) = ?', [$normalizedAuthor]);

            if ($book) {
                $query->where('id', '!=', $book->getKey());
            }

            if ($query->exists()) {
                $fail('The author "'.$value.'" already exists. Enter a different author.');
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

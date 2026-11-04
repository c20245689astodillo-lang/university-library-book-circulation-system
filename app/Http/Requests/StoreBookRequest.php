<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        foreach (['book_isbn', 'book_title', 'author_name'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => trim(strip_tags($value))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'publisher_id' => ['required', 'integer', Rule::exists('publishers', 'id')],
            'book_isbn' => ['required', 'string', 'max:20', Rule::unique('books', 'book_isbn')],
            'book_title' => ['required', 'string', 'max:255'],
            'author_name' => ['required', 'string', 'max:255'],
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
            'book_isbn.unique' => 'That ISBN is already assigned to another book.',
            'book_title.required' => 'Enter the book title.',
            'book_title.string' => 'The book title must be text.',
            'book_title.max' => 'The book title may not be longer than 255 characters.',
            'author_name.required' => 'Enter the author name.',
            'author_name.string' => 'The author name must be text.',
            'author_name.max' => 'The author name may not be longer than 255 characters.',
        ];
    }
}

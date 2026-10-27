<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'publisher_id' => ['required', 'integer', Rule::exists('publishers', 'id')],
            'book_isbn' => [
                'required', 'string', 'max:20',
                Rule::unique('books', 'book_isbn')->ignore($book?->id),
            ],
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
            'book_isbn.unique' => 'That ISBN is already assigned to another book.',
            'book_title.required' => 'Enter the book title.',
            'author_name.required' => 'Enter the author name.',
        ];
    }
}

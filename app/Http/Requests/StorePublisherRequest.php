<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublisherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $publisherName = $this->input('publisher_name');

        if (is_string($publisherName)) {
            $this->merge(['publisher_name' => trim(strip_tags($publisherName))]);
        }
    }

    public function rules(): array
    {
        return [
            'publisher_name' => [
                'required', 'string', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->validatePublisherName($value, $fail);
                },
                Rule::unique('publishers', 'publisher_name'),
            ],
        ];
    }

    protected function validatePublisherName(mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (preg_match('~^(?=.*\p{L})[\p{L}\p{M}\d&.,\'’() \-]+$~u', $value) !== 1) {
            $fail('Enter a publisher name using letters and common name punctuation.');

            return;
        }

        if (preg_match('~\p{L}\p{M}*\d~u', $value) === 1) {
            $fail('Do not append digits directly to a word in the publisher name.');

            return;
        }

        if (preg_match('~(\p{L})\1{4,}~iu', $value) === 1) {
            $fail('The publisher name cannot contain five or more repeated letters.');

            return;
        }

        if ($this->hasScrambledConsonantRun($value)) {
            $fail('The publisher name contains an unusual consonant sequence; check it for typing errors.');

            return;
        }

        if (preg_match('~\d{4,}~u', $value) === 1) {
            $fail('The publisher name cannot contain four or more consecutive digits.');
        }
    }

    private function hasScrambledConsonantRun(string $value): bool
    {
        $words = preg_split('~[^\p{L}\p{M}]+~u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            if (preg_match('~^[\p{Latin}\p{M}]+$~u', $word) !== 1) {
                continue;
            }

            $consonants = preg_match('~[aeiou]~i', $word) === 1
                ? '~[bcdfghjklmnpqrstvwxyz]{6,}~i'
                : '~[bcdfghjklmnpqrstvwxz]{6,}~i';

            if (preg_match($consonants, $word) === 1) {
                return true;
            }
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'publisher_name.required' => 'Enter the publisher name.',
            'publisher_name.max' => 'The publisher name may not be longer than 255 characters.',
            'publisher_name.unique' => 'That publisher is already listed.',
        ];
    }
}

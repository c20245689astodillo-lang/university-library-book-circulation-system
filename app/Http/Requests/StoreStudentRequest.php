<?php

namespace App\Http\Requests;

use App\Support\StudentNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $studentNumber = $this->input('student_number');
        $studentName = $this->input('student_name');
        $email = $this->input('student_email');

        if (is_string($studentNumber)) {
            $this->merge(['student_number' => StudentNumber::normalize($studentNumber)]);
        }

        if (is_string($studentName)) {
            $this->merge(['student_name' => trim(strip_tags($studentName))]);
        }

        if (is_string($email)) {
            $this->merge(['student_email' => Str::lower(trim(strip_tags($email)))]);
        }

    }

    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'not_in:USR-', 'max:20', Rule::unique('students', 'student_number')],
            'student_name' => [
                'required', 'string', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    if (preg_match('~^[\p{L}\p{M}]+(?:[ .,:\x27’\-]+[\p{L}\p{M}]+)*\.?$~u', $value) !== 1) {
                        $fail('Enter a name using letters, spaces, and common name punctuation only.');

                        return;
                    }

                    if (preg_match('~(\p{L})\1{4,}~iu', $value) === 1) {
                        $fail('The name cannot contain five or more repeated letters.');

                        return;
                    }

                    if ($this->hasScrambledConsonantRun($value)) {
                        $fail('The name contains an unusual consonant sequence; check it for typing errors.');

                        return;
                    }

                    if (preg_match('~(?:^|\s)\p{L}$~u', $value) === 1) {
                        $fail('Add a period after a single-letter initial.');
                    }
                },
            ],
            'student_email' => [
                'required', Rule::email()->withNativeValidation(), 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $emailParts = explode('@', $value);

                    if (count($emailParts) !== 2) {
                        return;
                    }

                    [$localPart, $domain] = $emailParts;

                    if (preg_match('~^[\p{L}\p{N}]+(?:[._+\-][\p{L}\p{N}]+)*$~u', $localPart) !== 1) {
                        $fail('Use letters, numbers, periods, underscores, plus signs, or hyphens before @.');

                        return;
                    }

                    if (preg_match('~^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$~i', $domain) !== 1) {
                        $fail('Enter an email with a valid domain, such as example.com.');

                        return;
                    }

                    if (preg_match('~(\p{L})\1{4,}~iu', $localPart) === 1) {
                        $fail('The email name cannot contain five or more repeated letters.');

                        return;
                    }

                    if ($this->hasScrambledConsonantRun($localPart)) {
                        $fail('The email name contains an unusual consonant sequence; check it for typing errors.');
                    }
                },
                Rule::unique('students', 'student_email'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.required' => 'Enter the student number.',
            'student_number.not_in' => 'Enter the student number after the USR- prefix.',
            'student_number.unique' => 'That student number is already in use.',
            'student_name.required' => 'Enter the student name.',
            'student_email.required' => 'Enter the student email.',
            'student_email.email' => 'Enter a valid email address.',
            'student_email.unique' => 'That student email is already in use.',
        ];
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
}

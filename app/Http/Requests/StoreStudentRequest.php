<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('student_email');

        if (is_string($email)) {
            $this->merge(['student_email' => Str::lower(trim($email))]);
        }
    }

    public function rules(): array
    {
        return [
            'student_number' => ['required', 'string', 'max:20', Rule::unique('students', 'student_number')],
            'student_name' => ['required', 'string', 'max:255'],
            'student_email' => ['required', 'email', 'max:255', Rule::unique('students', 'student_email')],
        ];
    }

    public function messages(): array
    {
        return [
            'student_number.required' => 'Enter the student number.',
            'student_number.unique' => 'That student number is already in use.',
            'student_name.required' => 'Enter the student name.',
            'student_email.required' => 'Enter the student email.',
            'student_email.email' => 'Enter a valid email address.',
            'student_email.unique' => 'That student email is already in use.',
        ];
    }
}

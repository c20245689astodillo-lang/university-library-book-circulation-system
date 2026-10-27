<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublisherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'publisher_name' => ['required', 'string', 'max:255', Rule::unique('publishers', 'publisher_name')],
        ];
    }

    public function messages(): array
    {
        return [
            'publisher_name.required' => 'Enter the publisher name.',
            'publisher_name.unique' => 'That publisher is already listed.',
        ];
    }
}

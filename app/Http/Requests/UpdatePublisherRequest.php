<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublisherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $publisher = $this->route('publisher');

        return [
            'publisher_name' => [
                'required', 'string', 'max:255',
                Rule::unique('publishers', 'publisher_name')->ignore($publisher?->id),
            ],
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

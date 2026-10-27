<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'loan_id' => [
                'required', 'integer', Rule::exists('loans', 'id'), Rule::unique('fines', 'loan_id'),
            ],
            'calculated_fine' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
        ];
    }

    public function messages(): array
    {
        return [
            'loan_id.required' => 'Choose a loan for this fine.',
            'loan_id.exists' => 'Choose a loan that exists.',
            'loan_id.unique' => 'That loan already has a fine record.',
            'calculated_fine.required' => 'Enter the fine amount.',
            'calculated_fine.min' => 'The fine amount cannot be negative.',
            'payment_status.required' => 'Choose a payment status.',
            'payment_status.in' => 'Choose either paid or unpaid.',
        ];
    }
}

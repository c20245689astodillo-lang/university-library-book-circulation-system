<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $paymentStatus = $this->input('payment_status');

        if (is_string($paymentStatus)) {
            $this->merge(['payment_status' => strtolower(trim($paymentStatus))]);
        }
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_status.required' => 'Choose a payment status.',
            'payment_status.in' => 'Choose either paid or unpaid.',
        ];
    }
}

<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

final class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_billing') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}

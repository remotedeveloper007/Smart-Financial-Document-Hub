<?php

namespace App\Http\Requests\Billing;

use App\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreSubscriptionCheckoutRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key', $this->input('idempotency_key')),
        ]);
    }

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
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'provider' => ['required', Rule::in(['stripe', 'razorpay', 'paypal'])],
            'billing_interval' => ['required', Rule::enum(BillingInterval::class)],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()
                    || $this->input('billing_interval') !== 'yearly'
                    || ! is_numeric($this->input('plan_id'))) {
                    return;
                }

                $plan = Plan::query()->find($this->integer('plan_id'));
                if ($plan && ! $plan->supportsInterval(BillingInterval::Yearly)) {
                    $validator->errors()->add('billing_interval', 'The selected plan is not available for yearly billing.');
                }
            },
        ];
    }
}

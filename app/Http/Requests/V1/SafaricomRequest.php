<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SafaricomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'msisdn'        => $this->has('msisdn') ? trim($this->input('msisdn'), '"\'') : null,
            'transactionId' => $this->has('transactionId') ? trim($this->input('transactionId'), '"\'') : null,
            'userStatus'    => $this->has('userStatus') ? trim($this->input('userStatus'), '"\'') : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'msisdn'        => ['required', 'string'],
            'transactionId' => ['required', 'string'],
            'userStatus'    => ['required', 'in:0,1'],
            'vendorName'    => ['sometimes', 'string'],
            'circle'        => ['sometimes', 'string'],
            'amount'        => ['sometimes'],
            'action'        => ['sometimes', 'string'],
            'operator'      => ['sometimes', 'string'],
            'channel'       => ['sometimes', 'string'],
            'packName'      => ['sometimes', 'string'],
            'startDate'     => ['sometimes'],
            'endDate'       => ['sometimes'],
            'language'      => ['sometimes', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'msisdn.required'        => 'The phone number (msisdn) is required.',
            'transactionId.required' => 'The transactionId is required for deduplication.',
            'userStatus.required'    => 'The userStatus is required (1 = subscribed, 0 = unsubscribed).',
            'userStatus.in'          => 'The userStatus must be 0 (unsubscribed) or 1 (subscribed).',
        ];
    }
}

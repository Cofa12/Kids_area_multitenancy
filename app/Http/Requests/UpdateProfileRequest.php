<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone'        => 'required|string',
            'name'         => 'string|sometimes',
            'password'     => 'sometimes',
            'campaign_id'  => 'sometimes|uuid|exists:tenant.campaigns,id',
            'referral_code' => 'sometimes|max:6|exists:tenant.users,referral_code',
        ];
    }
}

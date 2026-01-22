<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */

    /**
     * @psalm-suppress UnusedMethod
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
            'country' => 'required|string',
            'operator' => 'required|string',
            'service' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d|after:yesterday',
            'end_date' => 'sometimes|date_format:Y-m-d|after:startDate',
            'agency_id' => 'sometimes|string',
            'cpa' => 'sometimes|numeric|min:0',
            'type' => 'required|in:billable,non-billable',
            'influencer_id' => 'sometimes|string',
            'influencer_cost' => 'sometimes|numeric|min:0',
            'campaign_id' => 'required|string|exists:tenant.campaigns,id',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after' => 'start date must be after today'
        ];
    }
}

<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class VideoCreationRequest extends FormRequest
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
            'title_en' => 'sometimes|string|max:255',
            'title_ar' => 'sometimes|string|max:255',
            'description_en' => 'sometimes|string|max:255',
            'description_ar' => 'sometimes|string|max:255',
            'video_url_en' => 'sometimes|string',
            'video_url_ar' => 'sometimes|string',
            'thumbnail_url_en' => 'sometimes|file|mimes:jpeg,png,jpg,webp|mimetypes:image/jpeg,image/png,image/jpg,image/webp',
            'thumbnail_url_ar' => 'sometimes|file|mimes:jpeg,png,jpg,webp|mimetypes:image/jpeg,image/png,image/jpg,image/webp',
            'category_id' => 'required|exists:landlord.categories,id',
        ];
    }
}

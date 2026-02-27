<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ChildPhotoRequest extends FormRequest
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
            'photo' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,bmp,PNG,JPEG,GIF,SVG,WEBP,BMP',
            'description' => 'required|string'
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'The child photo is required.',
            'photo.file' => 'The uploaded file is invalid or failed to upload.',
            'photo.mimes' => 'The photo must be an image of type: jpeg, png, jpg, gif, svg, webp, or bmp.',
            'description.required' => 'A description for the photo is required.',
            'description.string' => 'The description must be a valid text string.',
        ];
    }
}

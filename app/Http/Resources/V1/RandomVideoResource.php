<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RandomVideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language');
        $lang = $lang ? substr($lang, 0, 2) : 'en';

        return [
            'title' => $lang == 'ar' ? $this->title_ar : $this->title_en,
            'description' => $lang == 'ar' ? $this->description_ar : $this->description_en,
            'url' => $lang == 'ar' ? $this->video_url_ar : $this->video_url_en,
            'thumbnail' => $lang == 'ar' ? $this->thumbnail_url_ar : $this->thumbnail_url_en,
        ];
    }
}

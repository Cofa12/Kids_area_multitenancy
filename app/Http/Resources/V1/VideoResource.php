<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language');
        return [
            'id'=>$this->id,
            'title'=>$lang=='ar'? $this->title_ar:$this->title_en,
            'description'=>$lang=='ar'?$this->description_ar:$this->description_en,
            'thumbnail_url'=>$lang=='ar'?$this->thumbnail_url_ar:$this->thumbnail_url_en,
            'created_at'=>$this->created_at,
        ];
    }
}

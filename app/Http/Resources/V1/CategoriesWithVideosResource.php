<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriesWithVideosResource extends JsonResource
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
            'title'=>$lang=='ar'?$this->title_ar:$this->title_en,
            'videos'=>$this->videos?$this->videos->whereNotNull('title_'.$lang)->take(6)->map(function ($video) use ($lang){
                return [
                    'id'=>$video->id,
                    'description'=>$lang=='ar'?$video->description_ar:$video->description_en,
                    'title'=>$lang=='ar'?$video->title_ar:$video->title_en,
                    'thumbnail_url'=>$lang=='ar'?$video->thumbnail_url_ar:$video->thumbnail_url_en,
                    'created_at'=>$video->created_at,
                ];
            }):[]
        ];
    }
}

<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowVideosOfCategory extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language');

        // Manual query construction to enforce landlord connection in testing environments
        $videos = \App\Models\Video::on('landlord')
            ->where('category_id', $this->id)
            ->whereNotNull('title_' . $lang)
            ->paginate(10);

        return [
            'videos' => $videos->getCollection()->transform(function ($video) use ($lang) {
                return [
                    'id' => $video->id,
                    'title' => $lang == 'ar' ? $video->title_ar : $video->title_en,
                    'thumbnail_url' => $lang == 'ar' ? $video->thumbnail_url_ar : $video->thumbnail_url_en,
                    'created_at' => $video->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $videos->currentPage(),
                'per_page' => $videos->perPage(),
                'total' => $videos->total(),
                'last_page' => $videos->lastPage(),
                'next_page_url' => $videos->nextPageUrl(),
                'prev_page_url' => $videos->previousPageUrl(),
            ],
        ];
    }
}

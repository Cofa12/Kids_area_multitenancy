<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAnalyticsResource extends JsonResource
{
    public function __construct(private int $numberOfUsers, private int $numberOfVideos, private int $numberOfChildPhotos)
    {
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'numberOfUsers'=>$this->numberOfUsers,
            'numberOfVideos'=>$this->numberOfVideos,
            'numberOfChildPhotos'=>$this->numberOfChildPhotos,
        ];
    }
}

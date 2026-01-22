<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildPhotosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'image_url'=>$this->image_url,
            'description'=>$this->description,
            'child_id'=>$this->child_id,
            'child_name'=>$this->child->name,
            'isAccepted'=>(int)$this->isAccepted,
            'created_at'=>$this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name  = explode("_", $this->name);

       return [
            'id' => $this->id,
            'category_name' => $name[0],
            'subject_name' => $this->subject->name,
            'student_count' => $this->students()->count() ,
        ];
    }

}

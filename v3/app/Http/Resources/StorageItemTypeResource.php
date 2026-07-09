<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for serializing a storage item type.
 */
class StorageItemTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'description'   => $this->description,
            'icon'          => $this->icon,
            'category_id'   => $this->category_id,
            'stackable'     => $this->stackable,
            'max_quantity'   => $this->max_quantity,
            'is_system'     => $this->is_system,
            'created_at'    => $this->created_at,
            'created_by'    => $this->created_by,
            'updated_at'    => $this->updated_at,
            'deleted_at'    => $this->deleted_at,
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
        ];
    }
}

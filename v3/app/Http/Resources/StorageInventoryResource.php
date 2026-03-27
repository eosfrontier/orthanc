<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for serializing a storage inventory row.
 */
class StorageInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'character_id' => $this->character_id,
            'item_type_id' => $this->item_type_id,
            'quantity'      => $this->quantity,
            'updated_at'   => $this->updated_at,
            'item_type'    => new StorageItemTypeResource($this->whenLoaded('itemType')),
        ];
    }
}

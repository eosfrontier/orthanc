<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for serializing a single item line within a label token.
 */
class StorageLabelTokenItemResource extends JsonResource
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
            'item_type_id' => $this->item_type_id,
            'quantity'     => $this->quantity,
            'item_type'    => new StorageItemTypeResource($this->whenLoaded('itemType')),
        ];
    }
}

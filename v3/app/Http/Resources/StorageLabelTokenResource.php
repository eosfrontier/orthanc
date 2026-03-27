<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for serializing a storage label token.
 */
class StorageLabelTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'token'           => $this->token,
            'item_type_id'    => $this->item_type_id,
            'quantity'        => $this->quantity,
            'note'            => $this->note,
            'source'          => $this->source,
            'source_char_id'  => $this->source_char_id,
            'created_by'      => $this->created_by,
            'created_at'      => $this->created_at,
            'claimed_by'      => $this->claimed_by,
            'claimed_at'      => $this->claimed_at,
            'expires_at'      => $this->expires_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for serializing a storage log entry.
 */
class StorageLogResource extends JsonResource
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
            'item_type_id'    => $this->item_type_id,
            'quantity'        => $this->quantity,
            'source_char_id'  => $this->source_char_id,
            'target_char_id'  => $this->target_char_id,
            'actor_joomla_id' => $this->actor_joomla_id,
            'action'          => $this->action,
            'brokered'        => $this->brokered,
            'note'            => $this->note,
            'created_at'      => $this->created_at,
        ];
    }
}

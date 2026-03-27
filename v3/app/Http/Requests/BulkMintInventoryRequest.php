<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a request to mint items into multiple characters' inventories at once.
 */
class BulkMintInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_type_id' => [
                'required',
                'integer',
                Rule::exists('ecc_storage_item_types', 'id')->whereNull('deleted_at'),
            ],
            'quantity'        => 'required|integer|min:1',
            'character_ids'   => 'required|array|max:200',
            'character_ids.*' => 'integer',
            'actor_id' => 'required|integer',
            'note'           => 'nullable|string|max:255',
        ];
    }
}

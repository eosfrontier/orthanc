<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a request to mint (grant) items into a character's inventory.
 */
class MintInventoryRequest extends FormRequest
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
            'character_id'   => 'required|integer',
            'item_type_id'   => [
                'required',
                'integer',
                Rule::exists('ecc_storage_item_types', 'id')->whereNull('deleted_at'),
            ],
            'quantity'        => 'required|integer|min:1',
            'actor_id'        => 'required|integer',
            'note'           => 'nullable|string|max:255',
        ];
    }
}

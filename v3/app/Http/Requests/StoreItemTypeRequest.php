<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a request to create a new storage item type.
 */
class StoreItemTypeRequest extends FormRequest
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
            'name'         => 'required|string|max:100|unique:ecc_storage_item_types,name',
            'category_id'  => [
                'required',
                'integer',
                Rule::exists('ecc_storage_categories', 'id')->whereNull('deleted_at'),
            ],
            'description'  => 'nullable|string|max:1000',
            'icon'         => 'nullable|string|max:100',
            'stackable'    => 'sometimes|boolean',
            'max_quantity'  => 'nullable|integer|min:1',
            'actor_id'      => 'required|integer',
        ];
    }
}

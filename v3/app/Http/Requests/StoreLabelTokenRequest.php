<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a request to create a new label token.
 */
class StoreLabelTokenRequest extends FormRequest
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
            'note'           => 'nullable|string|max:255',
            'source'         => 'required|string|max:50',
            'source_char_id' => 'nullable|integer',
            'created_by'     => 'required|integer',
        ];
    }
}

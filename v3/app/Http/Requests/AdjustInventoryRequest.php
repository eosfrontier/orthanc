<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a request to adjust (delta) a character's inventory quantity.
 */
class AdjustInventoryRequest extends FormRequest
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
            'quantity'        => 'required|integer',
            'actor_joomla_id' => 'required|integer',
            'note'           => 'nullable|string|max:255',
        ];
    }
}

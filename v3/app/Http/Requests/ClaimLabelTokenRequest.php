<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a request to claim a label token for a character.
 */
class ClaimLabelTokenRequest extends FormRequest
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
            'token'           => 'required|string|size:36',
            'character_id'    => 'required|integer',
            'actor_id'        => 'required|integer',
        ];
    }
}

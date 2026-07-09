<?php

namespace App\Http\Requests;

use App\Enums\LabelTokenSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a request to create a new label token with one or more item lines.
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
            'items'                => 'required|array|min:1|max:50',
            'items.*.item_type_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('ecc_storage_item_types', 'id')->whereNull('deleted_at'),
            ],
            'items.*.quantity'    => 'required|integer|min:1',
            'note'               => 'nullable|string|max:255',
            'source'             => ['required', Rule::in(LabelTokenSource::cases())],
            'source_char_id'     => 'nullable|required_if:source,burn|integer',
            'actor_id'           => 'required|integer',
            'expires_at'         => 'nullable|integer|min:0',
        ];
    }
}

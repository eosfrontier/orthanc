<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a request to update a storage setting.
 */
class UpdateSettingRequest extends FormRequest
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
        $key = $this->input('key');

        $valueRules = ['required', 'string'];

        if ($key === 'transfers_enabled') {
            $valueRules[] = 'in:0,1';
        } elseif ($key === 'broker_fee_sonuren') {
            $valueRules[] = 'integer';
            $valueRules[] = 'min:0';
        }

        return [
            'key'      => 'required|string|in:transfers_enabled,broker_fee_sonuren',
            'value'    => $valueRules,
            'actor_id' => 'required|integer',
        ];
    }
}

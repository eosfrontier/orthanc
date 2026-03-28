<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\StorageSettingResource;
use App\Models\StorageSetting;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Provides read and admin-write access to storage system settings.
 */
class SettingsController extends Controller
{
    /**
     * List all settings.
     */
    public function index(): AnonymousResourceCollection
    {
        return StorageSettingResource::collection(StorageSetting::all());
    }

    /**
     * Update a single setting by key. Unknown keys are rejected by the FormRequest.
     */
    public function update(UpdateSettingRequest $request): StorageSettingResource
    {
        $setting = StorageSetting::where('key_name', $request->validated('key'))->firstOrFail();

        $setting->update([
            'value'      => $request->validated('value'),
            'updated_at' => time(),
            'updated_by' => $request->validated('actor_id'),
        ]);

        return new StorageSettingResource($setting);
    }
}

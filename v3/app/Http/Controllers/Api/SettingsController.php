<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\StorageSettingResource;
use App\Models\StorageSetting;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Provides read and admin-write access to storage system settings.
 */
class SettingsController extends Controller
{
    /**
     * List all settings.
     */
    #[OA\Get(
        path: '/v3/storage/settings',
        summary: 'List all settings',
        security: [['bearerAuth' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of settings',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StorageSetting')),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return StorageSettingResource::collection(StorageSetting::all());
    }

    /**
     * Update a single setting by key. Unknown keys are rejected by the FormRequest.
     */
    #[OA\Put(
        path: '/v3/storage/settings',
        summary: 'Update a setting',
        description: 'Updates a single setting by key. Unknown keys are rejected. Known keys: transfers_enabled (0 or 1), broker_fee_sonuren (integer >= 0).',
        security: [['bearerAuth' => []]],
        tags: ['Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['key', 'value', 'actor_id'],
                properties: [
                    new OA\Property(property: 'key', type: 'string', example: 'transfers_enabled'),
                    new OA\Property(property: 'value', type: 'string', example: '1'),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Setting updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageSetting'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
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

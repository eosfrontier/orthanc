<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemTypeRequest;
use App\Http\Requests\UpdateItemTypeRequest;
use App\Http\Resources\StorageItemTypeResource;
use App\Models\StorageItemType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Handles CRUD operations for storage item types.
 *
 * Item types define the properties of storable items (stackable, max_quantity, etc.).
 * System item types (is_system = 1) are protected from update and deletion.
 */
class ItemTypeController extends Controller
{
    /**
     * List all active item types with their category eager-loaded.
     */
    #[OA\Get(
        path: '/v3/storage/item-types',
        summary: 'List all active item types',
        security: [['bearerAuth' => []]],
        tags: ['Item Types'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of item types',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StorageItemType')),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $itemTypes = StorageItemType::active()->with('category')->get();

        return StorageItemTypeResource::collection($itemTypes);
    }

    /**
     * Show a single active item type by ID with its category.
     */
    #[OA\Get(
        path: '/v3/storage/item-types/{id}',
        summary: 'Get a single item type',
        security: [['bearerAuth' => []]],
        tags: ['Item Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Item type details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageItemType'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function show(int $id): StorageItemTypeResource
    {
        $itemType = StorageItemType::active()->with('category')->findOrFail($id);

        return new StorageItemTypeResource($itemType);
    }

    /**
     * Create a new item type.
     */
    #[OA\Post(
        path: '/v3/storage/item-types',
        summary: 'Create a new item type',
        security: [['bearerAuth' => []]],
        tags: ['Item Types'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'category_id', 'actor_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Sonuren'),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Standard currency'),
                    new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'coin'),
                    new OA\Property(property: 'stackable', type: 'integer', enum: [0, 1], example: 1),
                    new OA\Property(property: 'max_quantity', type: 'integer', nullable: true, example: 999),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Item type created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageItemType'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function store(StoreItemTypeRequest $request): JsonResponse
    {
        $itemType = StorageItemType::create(array_merge(
            $request->safe()->except(['actor_id']),
            [
                'created_at' => time(),
                'created_by' => $request->validated('actor_id'),
                'updated_at' => time(),
            ]
        ));

        $itemType->load('category');

        return (new StorageItemTypeResource($itemType))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing item type. System item types cannot be modified.
     */
    #[OA\Put(
        path: '/v3/storage/item-types/{id}',
        summary: 'Update an item type',
        description: 'System item types (is_system = 1) cannot be modified.',
        security: [['bearerAuth' => []]],
        tags: ['Item Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Sonuren'),
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Standard currency'),
                    new OA\Property(property: 'icon', type: 'string', nullable: true, example: 'coin'),
                    new OA\Property(property: 'stackable', type: 'integer', enum: [0, 1], example: 1),
                    new OA\Property(property: 'max_quantity', type: 'integer', nullable: true, example: 999),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Item type updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageItemType'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function update(UpdateItemTypeRequest $request, int $id): StorageItemTypeResource
    {
        $itemType = StorageItemType::active()->findOrFail($id);

        if ($itemType->is_system) {
            abort(403, 'System item types cannot be modified.');
        }

        $itemType->update(array_merge(
            $request->validated(),
            ['updated_at' => time()]
        ));

        $itemType->load('category');

        return new StorageItemTypeResource($itemType);
    }

    /**
     * Soft-delete an item type. System item types cannot be deleted.
     */
    #[OA\Delete(
        path: '/v3/storage/item-types/{id}',
        summary: 'Soft-delete an item type',
        description: 'System item types (is_system = 1) cannot be deleted.',
        security: [['bearerAuth' => []]],
        tags: ['Item Types'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Item type deleted'),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function destroy(int $id): JsonResponse
    {
        $itemType = StorageItemType::active()->findOrFail($id);

        if ($itemType->is_system) {
            abort(403, 'System item types cannot be deleted.');
        }

        $itemType->update(['deleted_at' => time()]);

        return response()->json(null, 204);
    }
}

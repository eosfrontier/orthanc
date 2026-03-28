<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemTypeRequest;
use App\Http\Requests\UpdateItemTypeRequest;
use App\Http\Resources\StorageItemTypeResource;
use App\Models\StorageItemType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function index(): AnonymousResourceCollection
    {
        $itemTypes = StorageItemType::active()->with('category')->get();

        return StorageItemTypeResource::collection($itemTypes);
    }

    /**
     * Show a single active item type by ID with its category.
     */
    public function show(int $id): StorageItemTypeResource
    {
        $itemType = StorageItemType::active()->with('category')->findOrFail($id);

        return new StorageItemTypeResource($itemType);
    }

    /**
     * Create a new item type.
     */
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

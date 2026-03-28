<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\StorageCategoryResource;
use App\Models\StorageCategory;
use App\Models\StorageItemType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Handles CRUD operations for storage categories.
 *
 * Categories group item types into logical sets (e.g. "Currency", "Weapons").
 * System categories (is_system = 1) are protected from update and deletion.
 */
class CategoryController extends Controller
{
    /**
     * List all active categories.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = StorageCategory::active()->get();

        return StorageCategoryResource::collection($categories);
    }

    /**
     * Show a single active category by ID.
     */
    public function show(int $id): StorageCategoryResource
    {
        $category = StorageCategory::active()->findOrFail($id);

        return new StorageCategoryResource($category);
    }

    /**
     * Create a new category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = StorageCategory::create([
            'name'       => $request->validated('name'),
            'created_at' => time(),
            'created_by' => $request->validated('actor_id'),
        ]);

        return (new StorageCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing category. System categories cannot be renamed.
     */
    public function update(UpdateCategoryRequest $request, int $id): StorageCategoryResource
    {
        $category = StorageCategory::active()->findOrFail($id);

        if ($category->is_system) {
            abort(403, 'System categories cannot be modified.');
        }

        $category->update([
            'name' => $request->validated('name'),
        ]);

        return new StorageCategoryResource($category);
    }

    /**
     * Soft-delete a category. Rejected if system or has active item types.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = StorageCategory::active()->findOrFail($id);

        if ($category->is_system) {
            abort(403, 'System categories cannot be deleted.');
        }

        if (StorageItemType::active()->where('category_id', $id)->exists()) {
            abort(409, 'Cannot delete category with active item types.');
        }

        $category->update(['deleted_at' => time()]);

        return response()->json(null, 204);
    }
}

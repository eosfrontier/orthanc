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
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: '/v3/storage/categories',
        summary: 'List all active categories',
        security: [['bearerAuth' => []]],
        tags: ['Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of categories',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StorageCategory')),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $categories = StorageCategory::active()->get();

        return StorageCategoryResource::collection($categories);
    }

    /**
     * Show a single active category by ID.
     */
    #[OA\Get(
        path: '/v3/storage/categories/{id}',
        summary: 'Get a single category',
        security: [['bearerAuth' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageCategory'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function show(int $id): StorageCategoryResource
    {
        $category = StorageCategory::active()->findOrFail($id);

        return new StorageCategoryResource($category);
    }

    /**
     * Create a new category.
     */
    #[OA\Post(
        path: '/v3/storage/categories',
        summary: 'Create a new category',
        security: [['bearerAuth' => []]],
        tags: ['Categories'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'actor_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Weapons'),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageCategory'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
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
    #[OA\Put(
        path: '/v3/storage/categories/{id}',
        summary: 'Rename a category',
        description: 'System categories (is_system = 1) cannot be modified.',
        security: [['bearerAuth' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Armour'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageCategory'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
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
    #[OA\Delete(
        path: '/v3/storage/categories/{id}',
        summary: 'Soft-delete a category',
        description: 'Rejected if the category is a system category or has active item types.',
        security: [['bearerAuth' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Category deleted'),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/Conflict', response: 409),
        ],
    )]
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

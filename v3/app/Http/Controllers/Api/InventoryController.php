<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Requests\BulkMintInventoryRequest;
use App\Http\Requests\BurnInventoryRequest;
use App\Http\Requests\MintInventoryRequest;
use App\Http\Resources\StorageInventoryResource;
use App\Models\StorageInventory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Handles inventory operations: listing, minting, adjusting, burning, and bulk minting.
 */
class InventoryController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * List inventory for a character, eager-loading item type and category.
     */
    #[OA\Get(
        path: '/v3/storage/inventory',
        summary: 'List inventory for a character',
        security: [['bearerAuth' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'char_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Character inventory',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/StorageInventory')),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['char_id' => 'required|integer']);

        $inventory = StorageInventory::where('character_id', $request->query('char_id'))
            ->with('itemType.category')
            ->get();

        return StorageInventoryResource::collection($inventory);
    }

    /**
     * Mint items into a character's inventory.
     */
    #[OA\Post(
        path: '/v3/storage/inventory',
        summary: 'Mint items into a character\'s inventory',
        security: [['bearerAuth' => []]],
        tags: ['Inventory'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['character_id', 'item_type_id', 'quantity', 'actor_id'],
                properties: [
                    new OA\Property(property: 'character_id', type: 'integer', example: 100),
                    new OA\Property(property: 'item_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 10),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Quest reward'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Items minted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageInventory'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function store(MintInventoryRequest $request): JsonResponse
    {
        $inventory = $this->inventoryService->mint(
            $request->validated('character_id'),
            $request->validated('item_type_id'),
            $request->validated('quantity'),
            $request->validated('actor_id'),
            $request->validated('note'),
        );

        $inventory->load('itemType.category');

        return (new StorageInventoryResource($inventory))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Adjust an inventory row by a signed delta.
     */
    #[OA\Patch(
        path: '/v3/storage/inventory/{id}',
        summary: 'Adjust inventory quantity by a delta',
        security: [['bearerAuth' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity', 'actor_id'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', description: 'Signed delta (positive or negative)', example: -5),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Correction'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inventory adjusted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageInventory'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function adjust(AdjustInventoryRequest $request, int $id): StorageInventoryResource
    {
        $inventory = $this->inventoryService->adjust(
            $id,
            $request->validated('quantity'),
            $request->validated('actor_id'),
            $request->validated('note'),
        );

        $inventory->load('itemType.category');

        return new StorageInventoryResource($inventory);
    }

    /**
     * Burn (remove) items from a character's inventory.
     */
    #[OA\Delete(
        path: '/v3/storage/inventory/{id}',
        summary: 'Burn items from inventory',
        security: [['bearerAuth' => []]],
        tags: ['Inventory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity', 'actor_id'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 5),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Consumed in quest'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Items burned, returns updated inventory row',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageInventory'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function destroy(BurnInventoryRequest $request, int $id): JsonResponse
    {
        $inventoryRow = StorageInventory::findOrFail($id);

        $inventory = $this->inventoryService->burn(
            $inventoryRow->character_id,
            $inventoryRow->item_type_id,
            $request->validated('quantity'),
            $request->validated('actor_id'),
            $request->validated('note'),
        );

        $inventory->load('itemType.category');

        return (new StorageInventoryResource($inventory))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Bulk mint items to multiple characters. Returns 201/207/422 based on results.
     */
    #[OA\Post(
        path: '/v3/storage/inventory/bulk',
        summary: 'Bulk mint items to multiple characters',
        description: 'Returns 201 if all succeeded, 207 if partially succeeded, 422 if all failed.',
        security: [['bearerAuth' => []]],
        tags: ['Inventory'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['item_type_id', 'quantity', 'character_ids', 'actor_id'],
                properties: [
                    new OA\Property(property: 'item_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 10),
                    new OA\Property(property: 'character_ids', type: 'array', items: new OA\Items(type: 'integer'), minItems: 1, maxItems: 200, example: [100, 101, 102]),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Event reward'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'All mints succeeded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_attempted', type: 'integer', example: 3),
                        new OA\Property(property: 'total_succeeded', type: 'integer', example: 3),
                        new OA\Property(property: 'total_failed', type: 'integer', example: 0),
                        new OA\Property(property: 'succeeded', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'char_id', type: 'integer', example: 100),
                                new OA\Property(property: 'new_quantity', type: 'integer', example: 60),
                            ],
                            type: 'object',
                        )),
                        new OA\Property(property: 'failed', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'char_id', type: 'integer', example: 103),
                                new OA\Property(property: 'error', type: 'string', example: 'Max quantity exceeded'),
                            ],
                            type: 'object',
                        )),
                    ],
                ),
            ),
            new OA\Response(response: 207, description: 'Partial success — some mints failed'),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function bulk(BulkMintInventoryRequest $request): JsonResponse
    {
        $result = $this->inventoryService->bulk(
            $request->validated('item_type_id'),
            $request->validated('quantity'),
            $request->validated('character_ids'),
            $request->validated('actor_id'),
            $request->validated('note'),
        );

        $totalAttempted = count($result['succeeded']) + count($result['failed']);
        $totalSucceeded = count($result['succeeded']);
        $totalFailed = count($result['failed']);

        $response = [
            'total_attempted' => $totalAttempted,
            'total_succeeded' => $totalSucceeded,
            'total_failed'    => $totalFailed,
            'succeeded'       => array_map(fn ($s) => [
                'char_id'      => $s['character_id'],
                'new_quantity' => $s['new_quantity'],
            ], $result['succeeded']),
            'failed' => array_map(fn ($f) => [
                'char_id' => $f['character_id'],
                'error'   => $f['error'],
            ], $result['failed']),
        ];

        if ($totalFailed === 0) {
            $status = 201;
        } elseif ($totalSucceeded > 0) {
            $status = 207;
        } else {
            $status = 422;
        }

        return response()->json($response, $status);
    }
}

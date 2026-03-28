<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Requests\BulkMintInventoryRequest;
use App\Http\Requests\MintInventoryRequest;
use App\Http\Resources\StorageInventoryResource;
use App\Models\StorageInventory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function destroy(Request $request, int $id): JsonResponse
    {
        $inventoryRow = StorageInventory::findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'actor_id' => 'required|integer',
            'note'     => 'nullable|string|max:255',
        ]);

        $inventory = $this->inventoryService->burn(
            $inventoryRow->character_id,
            $inventoryRow->item_type_id,
            (int) $request->input('quantity'),
            (int) $request->input('actor_id'),
            $request->input('note'),
        );

        $inventory->load('itemType.category');

        return (new StorageInventoryResource($inventory))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Bulk mint items to multiple characters. Returns 201/207/422 based on results.
     */
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

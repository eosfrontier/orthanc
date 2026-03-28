<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\StorageInventoryResource;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;

/**
 * Handles item transfers between characters, including brokered transfers.
 *
 * Delegates entirely to TransferService; exceptions are handled globally
 * in bootstrap/app.php (DomainException → 422, TransfersLockedException → 423).
 */
class TransferController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly TransferService $transferService,
    ) {}

    /**
     * Transfer items from one character to another.
     */
    public function store(TransferRequest $request): JsonResponse
    {
        $result = $this->transferService->transfer(
            $request->validated('source_char_id'),
            $request->validated('target_char_id'),
            $request->validated('item_type_id'),
            $request->validated('quantity'),
            $request->validated('actor_id'),
            (bool) $request->validated('brokered', false),
            $request->validated('note'),
        );

        return response()->json([
            'data' => [
                'source' => new StorageInventoryResource($result['source']),
                'target' => new StorageInventoryResource($result['target']),
            ],
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\StorageInventoryResource;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: '/v3/storage/transfer',
        summary: 'Transfer items between characters',
        description: 'Transfers items from source to target character. Brokered transfers deduct a Sonuren fee from the sender. Returns 423 if transfers are disabled.',
        security: [['bearerAuth' => []]],
        tags: ['Transfers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['source_char_id', 'target_char_id', 'item_type_id', 'quantity', 'actor_id'],
                properties: [
                    new OA\Property(property: 'source_char_id', type: 'integer', example: 100),
                    new OA\Property(property: 'target_char_id', type: 'integer', example: 200),
                    new OA\Property(property: 'item_type_id', type: 'integer', example: 1),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 10),
                    new OA\Property(property: 'brokered', type: 'boolean', example: false),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Trade deal'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Transfer completed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'source', ref: '#/components/schemas/StorageInventory'),
                                new OA\Property(property: 'target', ref: '#/components/schemas/StorageInventory'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
            new OA\Response(ref: '#/components/responses/Locked', response: 423),
        ],
    )]
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

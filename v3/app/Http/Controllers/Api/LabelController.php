<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClaimLabelTokenRequest;
use App\Http\Requests\StoreLabelTokenRequest;
use App\Http\Resources\StorageLabelTokenResource;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Handles label token operations: listing, creation, and claiming.
 *
 * Label tokens are single-use physical tokens that can be claimed by a
 * character to receive items into their inventory.
 */
class LabelController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly LabelService $labelService,
    ) {}

    /**
     * List label tokens. Supports `?token=<uuid>` for single lookup
     * or `?unclaimed=1` for admin listing of all unclaimed tokens.
     */
    #[OA\Get(
        path: '/v3/storage/labels',
        summary: 'List or look up label tokens',
        description: 'Pass `?token=<uuid>` to look up a single token, or `?unclaimed=1` to list all unclaimed tokens. Without parameters, returns all tokens.',
        security: [['bearerAuth' => []]],
        tags: ['Labels'],
        parameters: [
            new OA\Parameter(name: 'token', in: 'query', required: false, description: 'UUID of a specific label token', schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'unclaimed', in: 'query', required: false, description: 'Set to 1 to list only unclaimed tokens', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Single token (when ?token=) or collection',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            oneOf: [
                                new OA\Schema(ref: '#/components/schemas/StorageLabelToken'),
                                new OA\Schema(type: 'array', items: new OA\Items(ref: '#/components/schemas/StorageLabelToken')),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound', response: 404),
        ],
    )]
    public function index(Request $request): StorageLabelTokenResource|AnonymousResourceCollection
    {
        if ($request->has('token')) {
            $token = $this->labelService->getByToken($request->query('token'));

            if ($token === null) {
                abort(404, 'Label token not found.');
            }

            return new StorageLabelTokenResource($token);
        }

        if ($request->boolean('unclaimed')) {
            return StorageLabelTokenResource::collection($this->labelService->getUnclaimed());
        }

        return StorageLabelTokenResource::collection($this->labelService->getAll());
    }

    /**
     * Create a new label token (mint or burn source).
     */
    #[OA\Post(
        path: '/v3/storage/labels',
        summary: 'Create a new label token',
        description: 'Creates a label token with attached items. Source "mint" creates fresh items; source "burn" pre-burns items from a character.',
        security: [['bearerAuth' => []]],
        tags: ['Labels'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items', 'source', 'actor_id'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['item_type_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'item_type_id', type: 'integer', example: 1),
                                new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 10),
                            ],
                            type: 'object',
                        ),
                        minItems: 1,
                        maxItems: 50,
                    ),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Starter pack'),
                    new OA\Property(property: 'source', type: 'string', enum: ['mint', 'burn'], example: 'mint'),
                    new OA\Property(property: 'source_char_id', type: 'integer', nullable: true, description: 'Required when source is "burn"', example: null),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                    new OA\Property(property: 'expires_at', type: 'integer', nullable: true, description: 'Unix timestamp', example: null),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Label token created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageLabelToken'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function store(StoreLabelTokenRequest $request): JsonResponse
    {
        $token = $this->labelService->create(
            $request->validated(),
            $request->validated('actor_id'),
        );

        return (new StorageLabelTokenResource($token))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Claim a label token, minting its items into a character's inventory.
     */
    #[OA\Post(
        path: '/v3/storage/labels/claim',
        summary: 'Claim a label token',
        description: 'Redeems a label token, minting its items into the specified character\'s inventory. Tokens can only be claimed once.',
        security: [['bearerAuth' => []]],
        tags: ['Labels'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'character_id', 'actor_id'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                    new OA\Property(property: 'character_id', type: 'integer', example: 100),
                    new OA\Property(property: 'actor_id', type: 'integer', example: 42),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token claimed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/StorageLabelToken'),
                    ],
                ),
            ),
            new OA\Response(ref: '#/components/responses/Unauthenticated', response: 401),
            new OA\Response(ref: '#/components/responses/Forbidden', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError', response: 422),
        ],
    )]
    public function claim(ClaimLabelTokenRequest $request): JsonResponse
    {
        $token = $this->labelService->claim(
            $request->validated('token'),
            $request->validated('character_id'),
            $request->validated('actor_id'),
        );

        return (new StorageLabelTokenResource($token))
            ->response()
            ->setStatusCode(200);
    }
}

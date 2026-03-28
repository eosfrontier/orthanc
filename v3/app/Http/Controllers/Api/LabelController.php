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

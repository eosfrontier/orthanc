<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StorageLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Provides read access to the storage audit log with UNION ALL optimised
 * character queries and manual pagination.
 */
class LogController extends Controller
{
    /**
     * List log entries with optional filters and manual pagination.
     *
     * Supports `char_id`, `item_type_id`, `include_brokered`, `per_page`, and `page` params.
     * Character queries use UNION ALL on source/target indexes for performance.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'char_id'           => 'sometimes|integer',
            'item_type_id'      => 'sometimes|integer',
            'include_brokered'  => 'sometimes|boolean',
            'per_page'          => 'sometimes|integer|min:1|max:200',
            'page'              => 'sometimes|integer|min:1',
        ]);

        $perPage = min((int) $request->query('per_page', 50), 200);
        $page = max((int) $request->query('page', 1), 1);
        $offset = ($page - 1) * $perPage;
        $includeBrokered = (bool) $request->query('include_brokered', false);
        $charId = $request->query('char_id');
        $itemTypeId = $request->query('item_type_id');

        if ($charId !== null) {
            $query = $this->buildCharQuery((int) $charId, $includeBrokered, $itemTypeId ? (int) $itemTypeId : null);
        } else {
            $query = DB::table('ecc_storage_log');

            if (! $includeBrokered) {
                $query->where('brokered', 0);
            }

            if ($itemTypeId !== null) {
                $query->where('item_type_id', (int) $itemTypeId);
            }
        }

        // Fetch one extra to determine has_more
        $rows = $query
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($perPage + 1)
            ->get();

        $hasMore = $rows->count() > $perPage;
        $rows = $rows->take($perPage);

        return response()->json([
            'data'     => StorageLogResource::collection($rows),
            'page'     => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Build a UNION ALL query for character-filtered log entries.
     *
     * Uses two sub-queries targeting the source_char_id and target_char_id
     * indexes respectively, then wraps in an outer query for ordering/pagination.
     *
     * @param int      $charId          The character ID to filter on.
     * @param bool     $includeBrokered Whether to include brokered rows.
     * @param int|null $itemTypeId      Optional item type filter.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildCharQuery(int $charId, bool $includeBrokered, ?int $itemTypeId): \Illuminate\Database\Query\Builder
    {
        $sourceQuery = DB::table('ecc_storage_log')
            ->where('source_char_id', $charId);

        $targetQuery = DB::table('ecc_storage_log')
            ->where('target_char_id', $charId);

        if (! $includeBrokered) {
            $sourceQuery->where('brokered', 0);
            $targetQuery->where('brokered', 0);
        }

        if ($itemTypeId !== null) {
            $sourceQuery->where('item_type_id', $itemTypeId);
            $targetQuery->where('item_type_id', $itemTypeId);
        }

        return DB::query()->fromSub(
            $sourceQuery->unionAll($targetQuery),
            'log'
        );
    }
}

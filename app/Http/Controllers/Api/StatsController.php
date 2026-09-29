<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatsRequest;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(private StatsService $statsService)
    {
    }

    public function stats(StatsRequest $request): JsonResponse
    {
        $stats = $this->statsService->getUserStats(
            $request->user(),
            $request->date('start_date'),
            $request->date('end_date'),
        );

        return response()->json(['data' => $stats]);
    }
}

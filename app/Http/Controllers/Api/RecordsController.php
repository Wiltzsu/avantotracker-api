<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RecordsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    public function __construct(private RecordsService $recordsService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->recordsService->getPersonalRecords($request->user()),
        ]);
    }
}

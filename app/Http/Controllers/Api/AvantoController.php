<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAvantoRequest;
use App\Http\Requests\StoreAvantoRequest;
use App\Http\Requests\UpdateAvantoRequest;
use App\Http\Resources\AvantoResource;
use App\Models\Avanto;
use App\Services\AvantoQueryService;
use App\Services\SelfieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvantoController extends Controller
{
    public function __construct(
        private AvantoQueryService $avantoQueryService,
        private SelfieService $selfieService,
    ) {
    }

    public function index(IndexAvantoRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->input('per_page', 10));
        $filters = $request->only(['location', 'start_date', 'end_date']);

        if ($request->has('sauna')) {
            $filters['sauna'] = $request->boolean('sauna');
        }

        $avantos = $this->avantoQueryService
            ->forUser($request->user(), $filters)
            ->paginate($perPage);

        return AvantoResource::collection($avantos);
    }

    public function store(StoreAvantoRequest $request): JsonResponse
    {
        $avanto = $request->user()->avantos()->create($request->validated());

        return (new AvantoResource($avanto))
            ->additional(['message' => 'Avanto session created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Avanto $avanto): AvantoResource
    {
        return new AvantoResource($avanto);
    }

    public function update(UpdateAvantoRequest $request, Avanto $avanto): JsonResponse
    {
        $avanto->update($request->validated());

        return (new AvantoResource($avanto))
            ->additional(['message' => 'Avanto session updated successfully'])
            ->response();
    }

    public function destroy(Avanto $avanto): JsonResponse
    {
        $this->selfieService->delete($avanto);
        $avanto->delete();

        return response()->json(['message' => 'Avanto session deleted successfully']);
    }
}

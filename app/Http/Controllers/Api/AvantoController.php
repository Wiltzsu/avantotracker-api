<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvantoRequest;
use App\Http\Requests\UpdateAvantoRequest;
use App\Http\Resources\AvantoResource;
use App\Models\Avanto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvantoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $avantos = $request->user()
            ->avantos()
            ->latest('date')
            ->paginate(10);

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
        $avanto->delete();

        return response()->json(['message' => 'Avanto session deleted successfully']);
    }
}

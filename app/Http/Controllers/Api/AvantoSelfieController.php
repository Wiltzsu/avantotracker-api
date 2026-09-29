<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadSelfieRequest;
use App\Http\Resources\AvantoResource;
use App\Models\Avanto;
use App\Services\SelfieService;
use Illuminate\Http\JsonResponse;

class AvantoSelfieController extends Controller
{
    public function __construct(private SelfieService $selfieService)
    {
    }

    public function store(UploadSelfieRequest $request, Avanto $avanto): JsonResponse
    {
        $this->selfieService->store($avanto, $request->file('selfie'));

        return (new AvantoResource($avanto->fresh()))
            ->additional(['message' => 'Selfie uploaded successfully'])
            ->response();
    }

    public function destroy(Avanto $avanto): JsonResponse
    {
        $this->selfieService->delete($avanto);

        return (new AvantoResource($avanto->fresh()))
            ->additional(['message' => 'Selfie removed successfully'])
            ->response();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Avanto;
use Illuminate\Http\Request;
use App\Http\Resources\AvantoResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class AvantoController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get all items
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $avantos = $request->user()
            ->avantos()
            ->latest('date')
            ->paginate(10);

        return AvantoResource::collection($avantos);
    }

    /**
     * Create new item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'water_temperature' => 'nullable|numeric|min:0|max:50',
            'duration_minutes' => 'nullable|integer|min:0|max:300',
            'duration_seconds' => 'nullable|integer|min:0|max:59',
            'swear_words' => 'nullable|integer|min:0',
            'feeling_before' => 'nullable|integer|min:1|max:10',
            'feeling_after' => 'nullable|integer|min:1|max:10',
            'sauna' => 'nullable|boolean',
            'sauna_duration' => 'nullable|integer|min:1|max:120',
        ]);

        $data['user_id'] = $request->user()->id;

        $avanto = Avanto::create($data);

        return response()->json([
            'message' => 'Avanto session created successfully',
            'data' => $avanto
        ], 201);
    }

    /**
     * Get single item
     *
     * @param Avanto $avanto
     * @return \App\Http\Resources\AvantoResource
     */
    public function show(Avanto $avanto): AvantoResource
    {
        // Prevent users from accessing other users' ice bath data
        $this->authorize('view', $avanto);

        return new AvantoResource($avanto);
    }

    /**
     * Show template for editing item
     */

    /**
     * Update item
     */
    public function update(Request $request, Avanto $avanto)
    {
        //
    }

    /**
     * Delete item
     */
    public function destroy(Avanto $avanto)
    {
        //
    }
}

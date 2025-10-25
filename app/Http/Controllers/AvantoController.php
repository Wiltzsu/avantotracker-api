<?php

namespace App\Http\Controllers;

use App\Models\Avanto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AvantoResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AvantoController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get all items
     */
    public function index(Request $request)
    {
        $avantos = $request->user()
            ->avantos()
            ->latest('date')
            ->paginate(10);

        return AvantoResource::collection($avantos);
    }

    /**
     * Create new item
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'water_temperature' => 'nullable|numeric|min:0|max:50',
            'duration_minutes' => 'nullable|integer|min:0|max:300',
            'duration_seconds' => 'nullable|integer|min:0|max:59',
            'swear_words' => 'nullable|integer|min:0',
            'feeling_before' => 'nullable|integer|min:1|max:10',
            'feeling_after' => 'nullable|integer|min:1|max:10',
            'selfie' => 'nullable|image|max:2048',
            'sauna' => 'nullable|boolean',
            'sauna_duration' => 'nullable|integer|min:1|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('selfie');
        $data['user_id'] = Auth::id();

        if ($request->hasFile('selfie')) {
            $data['selfie_path'] = $request->file('selfie')->store('selfies', 'public');
        }

        $avanto = Avanto::create($data);

        return response()->json([
            'message' => 'Avanto session created successfully',
            'data' => $avanto
        ], 201);
    }

    /**
     * Get single item
     */
    public function show(Avanto $avanto)
    {
        // Prevent users from accessing other users' ice bath data
        $this->authorize('view', $avanto);
        return new AvantoResource($avanto);
    }

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

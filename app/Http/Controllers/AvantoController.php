<?php

namespace App\Http\Controllers;

use App\Models\Avanto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AvantoController extends Controller
{
    public function index(): JsonResponse
    {
        $avantos = Avanto::where('user_id', Auth::id())
            ->orderBy('date', 'desc')
            ->paginate(10);

        return response()->json([
            'data' => $avantos,
            'meta' => [
                'current_page' => $avantos->currentPage(),
                'last_page' => $avantos->lastPage(),
                'total' => $avantos->total(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
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

    public function show(Avanto $avanto): JsonResponse
    {
        if ($avanto->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $avanto]);
    }

    public function update(Request $request, Avanto $avanto): JsonResponse
    {
        if ($avanto->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
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

        if ($request->hasFile('selfie')) {
            // Delete old selfie if exists
            if ($avanto->selfie_path) {
                Storage::disk('public')->delete($avanto->selfie_path);
            }
            $data['selfie_path'] = $request->file('selfie')->store('selfies', 'public');
        }

        $avanto->update($data);

        return response()->json([
            'message' => 'Avanto session updated successfully',
            'data' => $avanto
        ]);
    }

    public function destroy(Avanto $avanto): JsonResponse
    {
        if ($avanto->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete selfie file if exists
        if ($avanto->selfie_path) {
            Storage::disk('public')->delete($avanto->selfie_path);
        }

        $avanto->delete();

        return response()->json(['message' => 'Avanto session deleted successfully']);
    }

    public function getUserAvantos($userId): JsonResponse
    {
        if ($userId != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $avantos = Avanto::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['data' => $avantos]);
    }
}

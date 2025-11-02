<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StatsService;

class StatsController extends Controller
{
    //
    public function stats(Request $request)
    {
        $stats = app(StatsService::class)->getUserStats(
            $request->user(),
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json(['data' => $stats]);
    }
}

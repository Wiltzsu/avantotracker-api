<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAvantoRequest;
use App\Services\AvantoExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvantoExportController extends Controller
{
    public function __construct(private AvantoExportService $exportService)
    {
    }

    public function export(IndexAvantoRequest $request): StreamedResponse
    {
        return $this->exportService->exportCsv(
            $request->user(),
            $request->only(['location', 'start_date', 'end_date', 'sauna'])
        );
    }
}

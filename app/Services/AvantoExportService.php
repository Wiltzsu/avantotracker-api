<?php

namespace App\Services;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvantoExportService
{
    public function __construct(private AvantoQueryService $avantoQueryService)
    {
    }

    public function exportCsv(User $user, array $filters = []): StreamedResponse
    {
        $filename = 'avantotracker-export-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($user, $filters) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'date',
                'location',
                'water_temperature',
                'duration_minutes',
                'duration_seconds',
                'swear_words',
                'feeling_before',
                'feeling_after',
                'sauna',
                'sauna_duration',
            ]);

            $this->avantoQueryService
                ->forUser($user, $filters)
                ->orderBy('date')
                ->chunk(200, function ($avantos) use ($handle) {
                    foreach ($avantos as $avanto) {
                        fputcsv($handle, [
                            $avanto->date->toDateString(),
                            $avanto->location,
                            $avanto->water_temperature,
                            $avanto->duration_minutes,
                            $avanto->duration_seconds,
                            $avanto->swear_words,
                            $avanto->feeling_before,
                            $avanto->feeling_after,
                            $avanto->sauna === null ? null : ($avanto->sauna ? '1' : '0'),
                            $avanto->sauna_duration,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

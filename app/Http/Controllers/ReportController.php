<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    /**
     * One report as JSON: headers, rows and totals, shaped for the CSV
     * download link and the printable PDF the dashboard renders client-side.
     */
    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', ReportService::TYPES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return response()->json(
            $this->reports->data(
                $request->user(),
                (string) $validated['type'],
                $validated['from'] ?? null,
                $validated['to'] ?? null,
            ),
        );
    }

    /**
     * Stream one report as a CSV download.
     */
    public function csv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', ReportService::TYPES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $type = (string) $validated['type'];

        return response()->streamDownload(
            $this->reports->csv($request->user(), $type, $validated['from'] ?? null, $validated['to'] ?? null),
            $this->reports->filename($type),
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}

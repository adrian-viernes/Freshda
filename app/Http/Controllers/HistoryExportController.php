<?php

namespace App\Http\Controllers;

use App\Services\HistoryExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryExportController extends Controller
{
    public function __construct(private HistoryExportService $history) {}

    /**
     * Scan history payload powering the CSV and PDF export tools.
     */
    public function data(Request $request): JsonResponse
    {
        return response()->json(
            $this->history->payloadFor($request->user(), (string) $request->query('filter', 'all')),
        );
    }

    /**
     * Stream the authenticated user's scan history as a CSV download.
     */
    public function csv(Request $request): StreamedResponse
    {
        $filename = 'freshda-scan-history-'.$this->history->timestamp().'.csv';
        $scanQuery = $this->history->scanQuery($request->user(), (string) $request->query('filter', 'all'));

        return response()->streamDownload(function () use ($scanQuery): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Fish', 'Species', 'Freshness', 'Freshness %', 'Confidence', 'Date']);

            foreach ($scanQuery->cursor() as $scan) {
                $row = $this->history->row($scan);

                fputcsv($handle, [
                    $this->history->csvSafe((string) $row['fish']),
                    $this->history->csvSafe((string) $row['species']),
                    $this->history->csvSafe($row['freshness']),
                    $row['percentage'],
                    $row['confidence'],
                    $this->history->csvSafe($row['date']),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Reads and shapes a user's scan history for the export tools: the JSON the
 * print/PDF preview consumes and the rows the CSV download streams.
 */
class HistoryExportService
{
    /**
     * The payload behind the CSV and PDF export tools.
     *
     * @return array{user: array{name: string, email: string, role: string, member_since: string}, summary: array{total: int, fresh: int, moderate: int, not_fresh: int}, scans: array<int, array{fish: string, species: string, freshness: string, percentage: int, confidence: string, date: string}>}
     */
    public function payloadFor(User $user, string $rawFilter): array
    {
        $scans = $this->scanQuery($user, $rawFilter)->get();

        return [
            'user' => $this->user($user),
            'summary' => $this->summary($scans),
            'scans' => $scans->map(fn (Scan $scan): array => $this->row($scan))->all(),
        ];
    }

    /**
     * The user's scans, filtered by the freshness chip the caller picked.
     *
     * Cursor streams row-by-row from this query, so a vendor with thousands
     * of scans never needs the full result set in memory.
     */
    public function scanQuery(User $user, string $rawFilter): Builder
    {
        $filter = $this->filter($rawFilter);

        return Scan::query()
            ->where('user_id', $user->id)
            ->when($filter !== null, fn ($query) => $query->where('freshness', $filter))
            ->latest();
    }

    /**
     * Neutralise spreadsheet formula injection: species labels can originate
     * from batch scans, where the name comes straight from the browser.
     */
    public function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    /**
     * @return array{fish: string, species: string, freshness: string, percentage: int, confidence: string, date: string}
     */
    public function row(Scan $scan): array
    {
        return [
            'fish' => (string) ($scan->species ?? 'Unknown'),
            'species' => FishAiService::latinFor((string) ($scan->species ?? '')),
            'freshness' => $scan->freshnessLabel(),
            'percentage' => (int) $scan->freshness_percentage,
            'confidence' => number_format((float) $scan->species_confidence, 2),
            'date' => $scan->created_at->format('Y-m-d H:i'),
        ];
    }

    public function timestamp(): string
    {
        return now()->format('Ymd-His');
    }

    /**
     * Map the dashboard freshness filter chips to database values.
     */
    private function filter(string $rawFilter): ?string
    {
        return match ($rawFilter) {
            'fresh', 'moderate' => $rawFilter,
            'notfresh' => 'not_fresh',
            default => null,
        };
    }

    /**
     * @return array{name: string, email: string, role: string, member_since: string}
     */
    private function user(User $user): array
    {
        return [
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'role' => $user->roleLabel(),
            'member_since' => $user->created_at->format('F j, Y'),
        ];
    }

    /**
     * @return array{total: int, fresh: int, moderate: int, not_fresh: int}
     */
    private function summary(Collection $scans): array
    {
        return [
            'total' => $scans->count(),
            'fresh' => $scans->where('freshness', 'fresh')->count(),
            'moderate' => $scans->where('freshness', 'moderate')->count(),
            'not_fresh' => $scans->where('freshness', 'not_fresh')->count(),
        ];
    }
}

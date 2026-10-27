<?php

namespace App\Models;

use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sample_count',
        'fresh_count',
        'moderate_count',
        'not_fresh_count',
        'verdict',
        'confidence',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sample_count' => 'integer',
            'fresh_count' => 'integer',
            'moderate_count' => 'integer',
            'not_fresh_count' => 'integer',
            'confidence' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Majority class wins; a tie is settled by the worse freshness class so
     * doubt never defaults to "fresh".
     *
     * @param  array{fresh: int, moderate: int, notfresh: int}  $counts
     */
    public static function verdictFromCounts(array $counts): string
    {
        $highest = max($counts);

        if ($counts['notfresh'] === $highest) {
            return 'NOT FRESH';
        }

        if ($counts['moderate'] === $highest) {
            return 'MODERATE';
        }

        return 'FRESH';
    }

    /** The verdict as the CSS class the history and dashboard cards use. */
    public function verdictClass(): string
    {
        return match ($this->verdict) {
            'NOT FRESH' => 'notfresh',
            'MODERATE' => 'moderate',
            default => 'fresh',
        };
    }

    /** The notification severity a verdict of this kind deserves. */
    public function severity(): string
    {
        return match ($this->verdict) {
            'NOT FRESH' => 'error',
            'MODERATE' => 'warning',
            default => 'success',
        };
    }

    /** How the batch is listed in tables and exported reports. */
    public function displayName(): string
    {
        return 'Batch ('.$this->sample_count.' samples)';
    }
}

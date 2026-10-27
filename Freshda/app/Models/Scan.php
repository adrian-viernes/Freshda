<?php

namespace App\Models;

use App\Models\Concerns\HasFreshness;
use Database\Factories\ScanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scan extends Model
{
    /** @use HasFactory<ScanFactory> */
    use HasFactory, HasFreshness;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'batch_id',
        'image_path',
        'species',
        'species_confidence',
        'freshness',
        'freshness_percentage',
        'freshness_confidence',
        'source',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'species_confidence' => 'float',
            'freshness_percentage' => 'integer',
            'freshness_confidence' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** The notification severity a result of this freshness deserves. */
    public function severity(): string
    {
        return match ($this->freshness) {
            'not_fresh' => 'error',
            'moderate' => 'warning',
            default => 'success',
        };
    }
}

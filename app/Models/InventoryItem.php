<?php

namespace App\Models;

use App\Models\Concerns\HasFreshness;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory, HasFreshness;

    /** Quantity in kg below which stock is flagged as low. */
    public const LOW_STOCK_THRESHOLD = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'species',
        'quantity',
        'min_stock',
        'freshness',
        'freshness_percentage',
        'last_scanned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'min_stock' => 'float',
            'freshness_percentage' => 'integer',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** The item's own threshold when set, else the shared default. */
    public function lowStockThreshold(): float
    {
        return $this->min_stock ?? self::LOW_STOCK_THRESHOLD;
    }

    public function isLowStock(): bool
    {
        return $this->quantity < $this->lowStockThreshold();
    }
}

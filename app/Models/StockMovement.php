<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per quantity change: additions, manual adjustments, accepted
 * deliveries and removals. `change` is signed (kg in, kg out) and
 * `balance_after` is the item's quantity right after the change.
 */
class StockMovement extends Model
{
    public const TYPE_ADD = 'add';

    public const TYPE_ADJUST = 'adjust';

    public const TYPE_DELIVERY = 'delivery';

    public const TYPE_REMOVED = 'removed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inventory_item_id',
        'item_name',
        'change',
        'balance_after',
        'type',
        'reference',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'change' => 'float',
            'balance_after' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** The payload the stock movement list renders. */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'inventory_item_id' => $this->inventory_item_id,
            'item_name' => $this->item_name,
            'change' => $this->change,
            'balance_after' => $this->balance_after,
            'type' => $this->type,
            'type_label' => self::typeLabel($this->type),
            'reference' => $this->reference,
            'time' => $this->created_at->format('M j, Y'),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_ADD => 'Added',
            self::TYPE_ADJUST => 'Adjusted',
            self::TYPE_DELIVERY => 'Delivery Stocked',
            self::TYPE_REMOVED => 'Removed',
            default => ucfirst($type),
        };
    }
}

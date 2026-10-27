<?php

namespace App\Models;

use Database\Factories\DeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'fish_type',
        'delivery_date',
        'expected_quantity',
        'actual_quantity',
        'status',
        'inspection_notes',
        'rejection_reason',
        'accepted_at',
        'inventory_item_id',
        'stock_updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'expected_quantity' => 'float',
            'actual_quantity' => 'float',
            'accepted_at' => 'datetime',
            'stock_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    /** CSS badge modifier used by the deliveries table. */
    public function statusClass(): string
    {
        return $this->status;
    }

    /** The quantity the vendor said actually arrived, once recorded. */
    public function receivedQuantity(): float
    {
        return (float) ($this->actual_quantity ?? $this->expected_quantity);
    }

    /**
     * Actual minus expected, or null while the delivery is still waiting to
     * be checked in. A negative value means the supplier sent less.
     */
    public function variance(): ?float
    {
        if ($this->actual_quantity === null) {
            return null;
        }

        return round((float) $this->actual_quantity - (float) $this->expected_quantity, 2);
    }

    /** even | short | over — used to colour the variance in the table. */
    public function varianceClass(): string
    {
        $variance = $this->variance();

        if ($variance === null || $variance == 0.0) {
            return 'even';
        }

        return $variance < 0 ? 'short' : 'over';
    }

    public function varianceLabel(): string
    {
        $variance = $this->variance();

        if ($variance === null) {
            return 'Not checked yet';
        }

        if ($variance == 0.0) {
            return 'Matches expected';
        }

        return ($variance < 0 ? 'Short by ' : 'Over by ').abs($variance).' kg';
    }

    /**
     * The delivery shape the table rows, detail modal and API responses all
     * share, with the comparison fields the workflow derives.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $this->loadMissing(['supplier', 'inventoryItem']);

        return array_merge($this->toArray(), [
            'status_label' => $this->statusLabel(),
            'status_class' => $this->statusClass(),
            'received_quantity' => $this->receivedQuantity(),
            'variance' => $this->variance(),
            'variance_class' => $this->varianceClass(),
            'variance_label' => $this->varianceLabel(),
            'supplier_name' => $this->supplier?->name,
            'date_display' => $this->delivery_date?->format('Y-m-d'),
        ]);
    }
}

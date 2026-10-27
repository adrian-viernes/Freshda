<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'notes',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return $this->isActive() ? 'Active' : 'Inactive';
    }

    /** CSS badge modifier used by the suppliers table. */
    public function statusClass(): string
    {
        return $this->isActive() ? 'active' : 'inactive';
    }

    /** One-line contact summary for tables and search. */
    public function contactSummary(): string
    {
        return trim(($this->phone ?: '').(($this->phone && $this->email) ? ' · ' : '').($this->email ?: ''));
    }

    /**
     * The supplier shape the table rows, detail modal and API responses all
     * share, including the counters the page shows.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $this->loadCount('deliveries');

        return array_merge($this->toArray(), [
            'status_label' => $this->statusLabel(),
            'status_class' => $this->statusClass(),
            'contact_summary' => $this->contactSummary(),
            'deliveries_count' => $this->deliveries_count,
        ]);
    }
}

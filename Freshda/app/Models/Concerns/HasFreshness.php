<?php

namespace App\Models\Concerns;

/**
 * Freshness labels shared by everything that records a fish freshness result:
 * scans and the inventory rows they update. The wording and the CSS class the
 * dashboard uses belong to the model so no controller or view repeats them.
 */
trait HasFreshness
{
    /** The freshness value as the CSS class the dashboard badges expect. */
    public function freshnessClass(): string
    {
        return $this->freshness === 'not_fresh' ? 'notfresh' : ($this->freshness ?? 'fresh');
    }

    /** The freshness value as it is shown to the user. */
    public function freshnessLabel(): string
    {
        return match ($this->freshness) {
            'moderate' => 'Moderate',
            'not_fresh' => 'Not Fresh',
            default => 'Fresh',
        };
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_add_an_item_without_the_optional_fields(): void
    {
        $vendor = User::factory()->vendor()->create();

        $response = $this->actingAs($vendor)->postJson('/inventory', [
            'name' => 'Tilapia',
            'quantity' => 12,
        ]);

        $response->assertOk()
            ->assertJsonPath('item.name', 'Tilapia')
            ->assertJsonPath('item.species', 'Tilapia')
            ->assertJsonPath('item.freshness', 'fresh')
            ->assertJsonPath('item.freshness_percentage', 90);
    }

    public function test_customers_cannot_manage_inventory(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->postJson('/inventory', ['name' => 'Tilapia', 'quantity' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('inventory_items', 0);
    }

    /**
     * The vendor summary card is recalculated from the table after every add,
     * edit and delete, so the counters have to keep their own meaning: items
     * count rows, the sub-label carries the total weight, species counts unique
     * species and low stock counts rows below 3 kg.
     */
    public function test_the_inventory_summary_is_recalculated_from_the_real_rows(): void
    {
        $script = $this->dashboardScript();

        $this->assertStringContainsString('document.querySelectorAll("#inventoryBody tr[data-id]")', $script);
        $this->assertStringContainsString('totalEl.textContent = rows.length;', $script);
        $this->assertStringContainsString('weightEl.textContent = totalKg.toFixed(1);', $script);
        $this->assertStringContainsString('speciesEl.textContent = Object.keys(species).length;', $script);
        $this->assertStringContainsString('lowStockEl.textContent = lowStock;', $script);
    }

    /** Editing an item must not silently rename its species to "Unknown". */
    public function test_updating_an_item_keeps_its_species_when_the_form_omits_it(): void
    {
        $vendor = User::factory()->vendor()->create();

        $item = $vendor->inventoryItems()->create([
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 10,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        $this->actingAs($vendor)->patchJson('/inventory/'.$item->id, [
            'name' => 'Tilapia',
            'quantity' => 8,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ])->assertOk()->assertJsonPath('item.species', 'Tilapia');

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'species' => 'Tilapia',
        ]);
    }

    /** Restocks and repeat edits must not pile up low-stock warnings. */
    public function test_low_stock_warnings_only_fire_when_the_item_drops_below_the_threshold(): void
    {
        $vendor = User::factory()->vendor()->create();

        $item = $vendor->inventoryItems()->create([
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 10,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        /* Still healthy — no warning. */
        $this->actingAs($vendor)->patchJson('/inventory/'.$item->id, [
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 5,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ])->assertOk();

        $this->assertDatabaseMissing('app_notifications', [
            'user_id' => $vendor->id,
            'title' => 'Low Stock — Tilapia',
        ]);

        /* Crosses below 3 kg — one warning. */
        $this->actingAs($vendor)->patchJson('/inventory/'.$item->id, [
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 2,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ])->assertOk();

        $this->assertSame(1, $vendor->appNotifications()->where('title', 'Low Stock — Tilapia')->count());

        /* Staying low must not warn again. */
        $this->actingAs($vendor)->patchJson('/inventory/'.$item->id, [
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 1,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ])->assertOk();

        $this->assertSame(1, $vendor->appNotifications()->where('title', 'Low Stock — Tilapia')->count());
    }

    /** 9.20 — every quantity change leaves a signed ledger row. */
    public function test_adding_an_item_records_a_stock_movement(): void
    {
        $vendor = User::factory()->vendor()->create();

        $response = $this->actingAs($vendor)->postJson('/inventory', [
            'name' => 'Tuna',
            'quantity' => 12.5,
        ])->assertOk();

        $this->assertDatabaseHas('stock_movements', [
            'user_id' => $vendor->id,
            'item_name' => 'Tuna',
            'type' => 'add',
            'reference' => 'Item created',
        ]);

        $movement = $response->json('movement');
        $this->assertSame('add', $movement['type']);
        $this->assertEqualsWithDelta(12.5, (float) $movement['change'], 0.001);
        $this->assertEqualsWithDelta(12.5, (float) $movement['balance_after'], 0.001);
    }

    public function test_editing_the_quantity_records_an_adjustment_movement(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = $vendor->inventoryItems()->create([
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 10,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        /* A fresh edit that leaves the quantity alone must not move the ledger. */
        $this->actingAs($vendor)->patchJson('/inventory/'.$item->id, [
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 10,
            'freshness' => 'moderate',
            'freshness_percentage' => 70,
        ])->assertOk()->assertJsonPath('movement', null);

        $this->assertDatabaseCount('stock_movements', 0);

        /* Dropping to 4 kg books a -6 kg adjustment. */
        $response = $this->actingAs($vendor)->patchJson('/inventory/'.$item->id, [
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 4,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ])->assertOk();

        $movement = $response->json('movement');
        $this->assertSame('adjust', $movement['type']);
        $this->assertEqualsWithDelta(-6.0, (float) $movement['change'], 0.001);
        $this->assertEqualsWithDelta(4.0, (float) $movement['balance_after'], 0.001);
    }

    public function test_deleting_an_item_records_a_removal_movement(): void
    {
        $vendor = User::factory()->vendor()->create();
        $item = $vendor->inventoryItems()->create([
            'name' => 'Bangus',
            'species' => 'Chanos chanos',
            'quantity' => 7,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        $this->actingAs($vendor)->delete('/inventory/'.$item->id)->assertOk();

        $movement = $vendor->stockMovements()->firstOrFail();
        $this->assertSame('removed', $movement->type);
        $this->assertEqualsWithDelta(-7.0, (float) $movement->change, 0.001);
        /* The item row is gone, but the movement keeps the name for history. */
        $this->assertNull($movement->inventory_item_id);
        $this->assertSame('Bangus', $movement->item_name);
    }

    public function test_the_dashboard_payload_carries_the_vendor_movements_only(): void
    {
        $vendor = User::factory()->vendor()->create();
        $customer = User::factory()->create();
        $item = $vendor->inventoryItems()->create([
            'name' => 'Tuna',
            'species' => 'Tuna',
            'quantity' => 5,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);
        $vendor->stockMovements()->create([
            'inventory_item_id' => $item->id,
            'item_name' => 'Tuna',
            'change' => 5,
            'balance_after' => 5,
            'type' => 'add',
            'reference' => 'Item created',
        ]);

        $this->actingAs($vendor)->get('/dashboard')
            ->assertOk()
            ->assertSee('"movements":[{', false);

        $this->actingAs($customer)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('"movements":[', false);
    }

    /** Rule A — scanning is an analysis, never a stock transaction. */
    public function test_a_scan_records_no_stock_movement(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->inventoryItems()->create([
            'name' => 'Tilapia',
            'species' => 'Tilapia',
            'quantity' => 5,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        $this->actingAs($vendor)->post('/scans', [])->assertOk();

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_item_can_set_its_own_low_stock_threshold(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->postJson('/inventory', [
            'name' => 'Salmon',
            'quantity' => 10,
            'min_stock' => 20,
        ])->assertOk()->assertJsonPath('item.min_stock', 20);

        /* 10 kg is plenty under the default 3 kg, but low under its own 20. */
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $vendor->id,
            'title' => 'Low Stock — Salmon',
        ]);

        /* Clearing the threshold falls back to the shared default. */
        $item = $vendor->inventoryItems()->firstOrFail();
        $response = $this->actingAs($vendor)->patchJson('/inventory/'.$item->id, [
            'name' => 'Salmon',
            'quantity' => 10,
            'min_stock' => null,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ])->assertOk();

        $this->assertNull($response->json('item.min_stock'));
        $this->assertEqualsWithDelta(3.0, $item->fresh()->lowStockThreshold(), 0.001);
        $this->assertFalse($item->fresh()->isLowStock());
    }
}

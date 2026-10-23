<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): User
    {
        return User::factory()->vendor()->create();
    }

    public function test_a_vendor_can_record_an_incoming_delivery(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->create();

        $response = $this->actingAs($vendor)->postJson('/deliveries', [
            'supplier_id' => $supplier->id,
            'fish_type' => 'Milkfish',
            'delivery_date' => '2026-10-21',
            'expected_quantity' => 40,
        ]);

        $response->assertOk()
            ->assertJsonPath('delivery.fish_type', 'Milkfish')
            ->assertJsonPath('delivery.status', 'pending')
            ->assertJsonPath('delivery.supplier_name', $supplier->name);

        $this->assertEqualsWithDelta(40.0, $response->json('delivery.expected_quantity'), 0.001);

        $this->assertDatabaseHas('deliveries', [
            'user_id' => $vendor->id,
            'supplier_id' => $supplier->id,
            'status' => Delivery::STATUS_PENDING,
        ]);
    }

    public function test_a_delivery_must_point_at_one_of_the_vendors_own_suppliers(): void
    {
        $vendor = $this->vendor();
        $someoneElses = Supplier::factory()->for($this->vendor())->create();

        $this->actingAs($vendor)->postJson('/deliveries', [
            'supplier_id' => $someoneElses->id,
            'fish_type' => 'Tilapia',
            'delivery_date' => '2026-10-21',
            'expected_quantity' => 10,
        ])->assertJsonValidationErrors(['supplier_id']);

        $this->assertDatabaseCount('deliveries', 0);
    }

    /** 8.8 — an inactive supplier cannot be used for anything new. */
    public function test_an_inactive_supplier_cannot_receive_new_deliveries(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->inactive()->create();

        $this->actingAs($vendor)->postJson('/deliveries', [
            'supplier_id' => $supplier->id,
            'fish_type' => 'Tilapia',
            'delivery_date' => '2026-10-21',
            'expected_quantity' => 10,
        ])->assertJsonValidationErrors(['supplier_id']);

        $this->assertDatabaseCount('deliveries', 0);
    }

    public function test_customers_cannot_record_deliveries(): void
    {
        $customer = User::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAs($customer)->postJson('/deliveries', [
            'supplier_id' => $supplier->id,
            'fish_type' => 'Tilapia',
            'delivery_date' => '2026-10-21',
            'expected_quantity' => 10,
        ])->assertForbidden();

        $this->assertDatabaseCount('deliveries', 0);
    }

    /** 9.9 / 9.10 — the accepted quantity lands in stock exactly once. */
    public function test_accepting_a_delivery_stocks_the_inventory_exactly_once(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->create();
        $item = $vendor->inventoryItems()->create([
            'name' => 'Milkfish',
            'species' => 'Milkfish',
            'quantity' => 5,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);
        $delivery = $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Milkfish',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 10,
            'status' => Delivery::STATUS_PENDING,
        ]);

        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/accept', [
            'actual_quantity' => 8,
        ])->assertOk()
            ->assertJsonPath('delivery.status', 'accepted')
            ->assertJsonPath('delivery.inventory_item_id', $item->id)
            ->assertJsonPath('delivery.variance_class', 'short');

        $this->assertEqualsWithDelta(13.0, (float) $item->fresh()->quantity, 0.001);
        $this->assertNotNull($delivery->fresh()->stock_updated_at);

        /* A second acceptance must not stock the same delivery again. */
        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/accept', [
            'actual_quantity' => 8,
        ])->assertOk();

        $this->assertEqualsWithDelta(13.0, (float) $item->fresh()->quantity, 0.001);
        $this->assertSame(1, $vendor->appNotifications()->where('title', 'Delivery Accepted — Milkfish')->count());
    }

    /** A fish the vendor does not stock yet still needs somewhere to land. */
    public function test_accepting_a_delivery_creates_the_inventory_item_when_needed(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->create();
        $delivery = $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Bangus',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 15,
            'status' => Delivery::STATUS_PENDING,
        ]);

        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/accept', [])->assertOk();

        $item = InventoryItem::query()->where('user_id', $vendor->id)->where('name', 'Bangus')->firstOrFail();

        $this->assertEqualsWithDelta(15.0, (float) $item->quantity, 0.001);
    }

    /** 9.8 — rejecting records why, and stocks nothing. */
    public function test_rejecting_a_delivery_needs_a_reason_and_stocks_nothing(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->create();
        $delivery = $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Tilapia',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 20,
            'status' => Delivery::STATUS_PENDING,
        ]);

        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/reject', [
            'rejection_reason' => '',
        ])->assertJsonValidationErrors(['rejection_reason']);

        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/reject', [
            'rejection_reason' => 'Arrived warm',
        ])->assertOk()->assertJsonPath('delivery.status', 'rejected');

        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'rejection_reason' => 'Arrived warm',
        ]);
        $this->assertDatabaseCount('inventory_items', 0);
    }

    /** Stock that already moved cannot be un-moved by rejecting afterwards. */
    public function test_an_accepted_delivery_cannot_be_rejected(): void
    {
        $vendor = $this->vendor();
        $delivery = Delivery::factory()->accepted(12)->for($vendor)->create();
        $delivery->forceFill(['stock_updated_at' => now()])->save();

        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/reject', [
            'rejection_reason' => 'Changed our mind',
        ])->assertJsonValidationErrors(['status']);
    }

    /** 9.13 — only a delivery that is still pending may be edited. */
    public function test_only_pending_deliveries_can_be_edited(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->create();
        $pending = $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Tilapia',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 20,
            'status' => Delivery::STATUS_PENDING,
        ]);
        $accepted = Delivery::factory()->accepted(12)->for($vendor)->create(['supplier_id' => $supplier->id]);

        $edited = $this->actingAs($vendor)->patchJson('/deliveries/'.$pending->id, [
            'supplier_id' => $supplier->id,
            'fish_type' => 'Tilapia',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 25,
        ])->assertOk();

        $this->assertEqualsWithDelta(25.0, $edited->json('delivery.expected_quantity'), 0.001);

        $this->actingAs($vendor)->patchJson('/deliveries/'.$accepted->id, [
            'supplier_id' => $accepted->supplier_id,
            'fish_type' => 'Tilapia',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 99,
        ])->assertJsonValidationErrors(['status']);
    }

    /** A pending delivery never touched stock, so the menu can remove it. */
    public function test_a_pending_delivery_can_be_deleted(): void
    {
        $vendor = $this->vendor();
        $delivery = Delivery::factory()->pending(10)->for($vendor)->create();

        $this->actingAs($vendor)->deleteJson('/deliveries/'.$delivery->id)
            ->assertOk()
            ->assertJsonPath('deleted', true)
            ->assertJsonPath('id', $delivery->id);

        $this->assertDatabaseMissing('deliveries', ['id' => $delivery->id]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    /** A rejected delivery stocked nothing either, so it goes the same way. */
    public function test_a_rejected_delivery_can_be_deleted(): void
    {
        $vendor = $this->vendor();
        $delivery = Delivery::factory()->rejected('Spoiled on arrival')->for($vendor)->create();

        $this->actingAs($vendor)->deleteJson('/deliveries/'.$delivery->id)->assertOk();

        $this->assertDatabaseMissing('deliveries', ['id' => $delivery->id]);
    }

    /** The inventory it stocked must keep the delivery that explains it. */
    public function test_an_accepted_delivery_that_stocked_inventory_cannot_be_deleted(): void
    {
        $vendor = $this->vendor();
        $delivery = Delivery::factory()->accepted(12)->for($vendor)->create();
        $delivery->forceFill(['stock_updated_at' => now()])->save();

        $this->actingAs($vendor)->deleteJson('/deliveries/'.$delivery->id)
            ->assertJsonValidationErrors(['delivery']);

        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id]);
    }

    public function test_a_vendor_cannot_delete_another_vendors_delivery(): void
    {
        $owner = $this->vendor();
        $other = $this->vendor();
        $delivery = Delivery::factory()->for($owner)->create();

        $this->actingAs($other)->deleteJson('/deliveries/'.$delivery->id)->assertForbidden();

        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id]);
    }

    /** Pending rows keep Check In / Reject; the rest sits in the three dots. */
    public function test_the_delivery_row_actions_live_in_a_three_dot_menu(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->create();
        $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Pompano',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 30,
            'status' => Delivery::STATUS_PENDING,
        ]);

        $html = (string) $this->actingAs($vendor)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-scope="delivery"', $html);
        $this->assertStringContainsString('data-action="details"', $html);
        $this->assertStringContainsString('data-action="delete"', $html);
        $this->assertStringContainsString('accept-delivery-btn', $html);
        $this->assertStringContainsString('reject-delivery-btn', $html);
        /* Edit moved out of the cell and into the menu. */
        $this->assertStringNotContainsString('edit-delivery-btn', $html);
    }

    /** The page, its sidebar link and its script all have to line up. */
    public function test_the_delivery_page_is_wired_into_the_dashboard(): void
    {
        $vendor = $this->vendor();
        $supplier = Supplier::factory()->for($vendor)->create();
        $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Pompano',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 30,
            'status' => Delivery::STATUS_PENDING,
        ]);

        $this->actingAs($vendor)->get('/dashboard')
            ->assertOk()
            ->assertSee('data-page="deliveries"', false)
            ->assertSee('id="deliveries-section"', false)
            ->assertSee('Pompano', false)
            ->assertSee('>Pending</span>', false)

            /* The Quick Actions tile links Deliveries instead of Batch Sampling. */
            ->assertSee('class="action-btn" data-page="deliveries"', false)
            ->assertDontSee('class="action-btn" data-page="batch"', false);

        $script = $this->dashboardScript();

        $this->assertStringContainsString('"deliveries",', $script);
        $this->assertStringContainsString('"/deliveries/" + delivery.id + "/accept"', $script);
    }

    /** Accepting a delivery is the only step that books a delivery movement. */
    public function test_accepting_a_delivery_records_a_delivery_movement(): void
    {
        $vendor = $this->vendor();
        $delivery = Delivery::factory()->pending(10)->for($vendor)->create();

        $response = $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/accept', [
            'actual_quantity' => 11,
        ])->assertOk();

        $this->assertDatabaseHas('stock_movements', [
            'user_id' => $vendor->id,
            'item_name' => $delivery->fish_type,
            'type' => 'delivery',
            'reference' => 'Delivery #'.$delivery->id,
        ]);

        $movement = $response->json('movement');
        $this->assertSame('delivery', $movement['type']);
        $this->assertSame('Delivery #'.$delivery->id, $movement['reference']);
        $this->assertEqualsWithDelta(11.0, (float) $movement['change'], 0.001);
    }

    public function test_rejecting_a_delivery_records_no_movement(): void
    {
        $vendor = $this->vendor();
        $delivery = Delivery::factory()->pending(10)->for($vendor)->create();

        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/reject', [
            'rejection_reason' => 'Spoiled on arrival',
        ])->assertOk();

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_accepting_twice_records_only_one_movement(): void
    {
        $vendor = $this->vendor();
        $delivery = Delivery::factory()->pending(10)->for($vendor)->create();

        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/accept', [
            'actual_quantity' => 10,
        ])->assertOk();
        $this->actingAs($vendor)->patchJson('/deliveries/'.$delivery->id.'/accept', [
            'actual_quantity' => 10,
        ])->assertOk();

        $this->assertSame(1, $vendor->stockMovements()->where('type', 'delivery')->count());
        $this->assertEqualsWithDelta(10.0, (float) $delivery->fresh()->inventoryItem->quantity, 0.001);
    }
}

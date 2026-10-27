<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vendor_can_register_a_supplier_with_the_required_details(): void
    {
        $vendor = User::factory()->vendor()->create();

        $response = $this->actingAs($vendor)->postJson('/suppliers', [
            'name' => 'Dela Cruz Fish Supply',
            'phone' => '09171234567',
            'address' => 'Batangas City',
        ]);

        $response->assertOk()
            ->assertJsonPath('supplier.name', 'Dela Cruz Fish Supply')
            ->assertJsonPath('supplier.status', 'active')
            ->assertJsonPath('supplier.status_label', 'Active')
            ->assertJsonPath('supplier.deliveries_count', 0);

        $this->assertDatabaseHas('suppliers', [
            'user_id' => $vendor->id,
            'name' => 'Dela Cruz Fish Supply',
        ]);
    }

    /** 8.10 — a supplier is not saved without the details it is filed under. */
    public function test_a_supplier_record_needs_a_name_and_a_phone(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->postJson('/suppliers', [
            'name' => '',
            'phone' => '',
        ])->assertJsonValidationErrors(['name', 'phone']);

        $this->assertDatabaseCount('suppliers', 0);
    }

    /** Two rows with one name would split that supplier's delivery history. */
    public function test_a_vendor_cannot_register_the_same_supplier_name_twice(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->suppliers()->create(['name' => 'Dela Cruz Fish Supply', 'phone' => '09171234567']);

        $this->actingAs($vendor)->postJson('/suppliers', [
            'name' => 'Dela Cruz Fish Supply',
            'phone' => '09171234567',
        ])->assertJsonValidationErrors(['name']);
    }

    /** D12 — the unique index backs the pre-check when two requests race. */
    public function test_the_supplier_name_rule_is_also_enforced_by_the_database(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->suppliers()->create(['name' => 'Dela Cruz Fish Supply', 'phone' => '09171234567']);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23000');

        /* Straight at the schema, bypassing the controller's pre-check. */
        $vendor->suppliers()->create(['name' => 'Dela Cruz Fish Supply', 'phone' => '09179999999']);
    }

    public function test_customers_cannot_manage_suppliers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->postJson('/suppliers', ['name' => 'Walk In Supply', 'phone' => '09170000000'])
            ->assertForbidden();

        /* The supplier tools are not even offered on a customer's dashboard. */
        $this->actingAs($customer)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('data-page="suppliers"', false)
            ->assertDontSee('id="suppliers-section"', false);

        $this->assertDatabaseCount('suppliers', 0);
    }

    /** The edit form posts every field, so an emptied email really clears. */
    public function test_a_vendor_can_edit_a_supplier_and_clear_an_optional_field(): void
    {
        $vendor = User::factory()->vendor()->create();
        $supplier = $vendor->suppliers()->create([
            'name' => 'Dela Cruz Fish Supply',
            'phone' => '09171234567',
            'email' => 'old@example.com',
            'address' => 'Batangas City',
        ]);

        $this->actingAs($vendor)->patchJson('/suppliers/'.$supplier->id, [
            'name' => 'Dela Cruz Trading',
            'phone' => '09189998888',
            'email' => null,
            'address' => 'Batangas City',
        ])->assertOk()
            ->assertJsonPath('supplier.name', 'Dela Cruz Trading')
            ->assertJsonPath('supplier.email', null);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'phone' => '09189998888',
            'email' => null,
        ]);
    }

    /** 8.8 — deactivating keeps the deliveries the supplier already made. */
    public function test_deactivating_a_supplier_keeps_its_delivery_history(): void
    {
        $vendor = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($vendor)->create();

        $delivery = $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Milkfish',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 20,
            'status' => 'pending',
        ]);

        $this->actingAs($vendor)->patchJson('/suppliers/'.$supplier->id.'/status', [
            'status' => 'inactive',
        ])->assertOk()
            ->assertJsonPath('supplier.status', 'inactive')
            ->assertJsonPath('supplier.status_label', 'Inactive');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('deliveries', ['id' => $delivery->id, 'supplier_id' => $supplier->id]);
        $this->assertSame(1, $supplier->deliveries()->count());
    }

    public function test_a_status_change_only_accepts_the_two_supported_states(): void
    {
        $vendor = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($vendor)->create();

        $this->actingAs($vendor)->patchJson('/suppliers/'.$supplier->id.'/status', [
            'status' => 'archived',
        ])->assertJsonValidationErrors(['status']);
    }

    public function test_a_vendor_cannot_touch_another_vendors_supplier(): void
    {
        $owner = User::factory()->vendor()->create();
        $other = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($owner)->create();

        $this->actingAs($other)->patchJson('/suppliers/'.$supplier->id.'/status', [
            'status' => 'inactive',
        ])->assertForbidden();
    }

    /** The supplier form files contact details only — no email box to fill. */
    public function test_the_supplier_form_asks_for_contact_details_only(): void
    {
        $script = $this->dashboardScript();

        $this->assertStringContainsString('Contact person', $script);
        $this->assertStringContainsString('Phone number *', $script);
        $this->assertStringNotContainsString('id="supplierEmail"', $script);
        /* An edit still hands back what is on file so nothing is wiped. */
        $this->assertStringContainsString(
            'email: isEdit ? supplier.email || null : null,',
            $script,
        );
    }

    /** The three-dot Delete only removes a supplier nothing points at yet. */
    public function test_a_vendor_can_delete_a_supplier_that_has_no_deliveries(): void
    {
        $vendor = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($vendor)->create();

        $this->actingAs($vendor)->deleteJson('/suppliers/'.$supplier->id)
            ->assertOk()
            ->assertJsonPath('deleted', true)
            ->assertJsonPath('id', $supplier->id);

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    /** 8.10 — with history on file the supplier is deactivated, never deleted. */
    public function test_a_supplier_with_delivery_history_cannot_be_deleted(): void
    {
        $vendor = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($vendor)->create();

        $delivery = $vendor->deliveries()->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Milkfish',
            'delivery_date' => now()->toDateString(),
            'expected_quantity' => 20,
            'status' => 'pending',
        ]);

        $this->actingAs($vendor)->deleteJson('/suppliers/'.$supplier->id)
            ->assertJsonValidationErrors(['supplier']);

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseHas('deliveries', [
            'id' => $delivery->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_a_vendor_cannot_delete_another_vendors_supplier(): void
    {
        $owner = User::factory()->vendor()->create();
        $other = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($owner)->create();

        $this->actingAs($other)->deleteJson('/suppliers/'.$supplier->id)->assertForbidden();

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    /** The action column is one three-dot menu carrying every row action. */
    public function test_the_supplier_row_actions_live_in_a_three_dot_menu(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->suppliers()->create([
            'name' => 'Batangas Fish Co',
            'phone' => '09171234567',
        ]);

        $html = (string) $this->actingAs($vendor)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('class="row-actions-toggle"', $html);
        $this->assertStringContainsString('data-scope="supplier"', $html);
        $this->assertStringContainsString('data-action="details"', $html);
        $this->assertStringContainsString('data-action="edit"', $html);
        $this->assertStringContainsString('data-action="delete"', $html);

        $script = $this->dashboardScript();

        $this->assertStringContainsString('"DELETE",', $script);
        $this->assertStringContainsString('"/suppliers/" + supplier.id,', $script);
        /* The old per-row buttons are gone with it. */
        $this->assertStringNotContainsString('supplier-details-btn', $html);
    }

    /** The page, its sidebar link and its script all have to line up. */
    public function test_the_supplier_page_is_wired_into_the_dashboard(): void
    {
        $vendor = User::factory()->vendor()->create();
        $vendor->suppliers()->create([
            'name' => 'Batangas Fish Co',
            'phone' => '09171234567',
            'status' => 'inactive',
        ]);

        $this->actingAs($vendor)->get('/dashboard')
            ->assertOk()
            ->assertSee('data-page="suppliers"', false)
            ->assertSee('id="suppliers-section"', false)
            /* The Quick Actions tile on the dashboard overview. */
            ->assertSee('class="action-btn" data-page="suppliers"', false)
            ->assertSee('Batangas Fish Co', false)
            ->assertSee('>Inactive</span>', false)
            /* The phone drawer that makes the sidebar reachable at all. */
            ->assertSee('id="sidebarToggle"', false)
            ->assertSee('id="sidebarBackdrop"', false);

        $script = $this->dashboardScript();

        $this->assertStringContainsString('"suppliers",', $script);
        $this->assertStringContainsString('"/suppliers/" + supplier.id + "/status"', $script);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Scan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    /** Reports are a vendor tool: customers are turned away at both ends. */
    public function test_customers_cannot_fetch_or_download_reports(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->getJson('/reports/data?type=inventory')
            ->assertForbidden();

        $this->actingAs($customer)
            ->getJson('/reports/export/csv?type=inventory')
            ->assertForbidden();
    }

    public function test_an_unknown_report_type_is_rejected(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)
            ->getJson('/reports/data?type=payroll')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    /** 12.1 — the assessment report counts only standalone, in-window scans. */
    public function test_the_assessment_report_counts_only_the_vendors_own_recent_scans(): void
    {
        $vendor = User::factory()->vendor()->create();
        $other = User::factory()->vendor()->create();

        $fresh = Scan::factory()->create([
            'user_id' => $vendor->id,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
            'created_at' => now()->subDays(2),
        ]);
        Scan::factory()->create([
            'user_id' => $vendor->id,
            'freshness' => 'not_fresh',
            'freshness_percentage' => 30,
            'created_at' => now()->subDays(5),
        ]);
        /* Out of the requested window. */
        Scan::factory()->create([
            'user_id' => $vendor->id,
            'created_at' => now()->subMonths(3),
        ]);
        /* Another vendor's scan never leaks in. */
        Scan::factory()->create(['user_id' => $other->id]);
        /* Batch samples roll up under their batch row instead. */
        $batch = Batch::factory()->create(['user_id' => $vendor->id]);
        Scan::factory()->create(['user_id' => $vendor->id, 'batch_id' => $batch->id]);

        $response = $this->actingAs($vendor)->getJson(
            '/reports/data?type=assessment&from='.now()->subWeek()->toDateString().'&to='.now()->toDateString(),
        )->assertOk();

        $this->assertCount(2, $response->json('rows'));
        $this->assertSame(2, $response->json('totals.total_scans'));
        $this->assertSame(1, $response->json('totals.fresh'));
        $this->assertSame(1, $response->json('totals.not_fresh'));
        $this->assertEqualsWithDelta(60.0, (float) $response->json('totals.average_freshness_percent'), 0.01);
        $this->assertSame($fresh->species, $response->json('rows.0.1'));
    }

    /** 12.3 — the inventory report totals the current balances and flags. */
    public function test_the_inventory_report_totals_stock_and_low_stock_flags(): void
    {
        $vendor = User::factory()->vendor()->create();
        $other = User::factory()->vendor()->create();

        InventoryItem::factory()->for($vendor)->create([
            'name' => 'Tuna',
            'quantity' => 12.5,
            'min_stock' => null,
        ]);
        InventoryItem::factory()->belowOwnThreshold()->for($vendor)->create(['name' => 'Salmon']);
        /* 2 kg sits under the shared default of 3 kg. */
        InventoryItem::factory()->for($vendor)->create([
            'name' => 'Bangus',
            'quantity' => 2,
            'min_stock' => null,
        ]);
        InventoryItem::factory()->for($other)->create();

        $response = $this->actingAs($vendor)->getJson('/reports/data?type=inventory')->assertOk();

        $this->assertCount(3, $response->json('rows'));
        $this->assertSame(3, $response->json('totals.items'));
        $this->assertSame(24.5, $response->json('totals.total_kg'));
        $this->assertSame(2, $response->json('totals.low_stock'));

        $tunaRow = collect($response->json('rows'))->firstWhere(0, 'Tuna');
        $this->assertSame(3.0, (float) $tunaRow[3]);
        $this->assertSame('No', $tunaRow[4]);
    }

    /** 12.4 — the low-stock report lists only short items, with the gap. */
    public function test_the_low_stock_report_lists_only_items_under_their_threshold(): void
    {
        $vendor = User::factory()->vendor()->create();

        InventoryItem::factory()->belowOwnThreshold()->for($vendor)->create(['name' => 'Salmon']);
        InventoryItem::factory()->for($vendor)->create([
            'name' => 'Tuna',
            'quantity' => 40,
            'min_stock' => null,
        ]);

        $response = $this->actingAs($vendor)->getJson('/reports/data?type=lowstock')->assertOk();

        $this->assertCount(1, $response->json('rows'));
        $this->assertSame('Salmon', $response->json('rows.0.0'));
        $this->assertEqualsWithDelta(10.0, (float) $response->json('rows.0.2'), 0.01);
        $this->assertEqualsWithDelta(10.0, (float) $response->json('rows.0.4'), 0.01);
        $this->assertSame(1, $response->json('totals.low_stock_items'));
        $this->assertEqualsWithDelta(10.0, (float) $response->json('totals.total_short_kg'), 0.01);
    }

    /** 12.2 — promised versus received, respecting the date window. */
    public function test_the_delivery_report_respects_the_date_range_and_statuses(): void
    {
        $vendor = User::factory()->vendor()->create();
        $other = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($vendor)->create(['name' => 'Lingayen Aquafarm']);

        Delivery::factory()->accepted(12)->for($vendor)->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Milkfish',
            'expected_quantity' => 10,
            'delivery_date' => now()->subDays(2),
        ]);
        Delivery::factory()->pending(8)->for($vendor)->create([
            'supplier_id' => $supplier->id,
            'delivery_date' => now()->subDays(3),
        ]);
        Delivery::factory()->rejected('Spoiled')->for($vendor)->create([
            'supplier_id' => $supplier->id,
            'expected_quantity' => 12,
            'delivery_date' => now()->subDays(4),
        ]);
        /* Outside the window and owned by someone else. */
        Delivery::factory()->pending(5)->for($vendor)->create([
            'delivery_date' => now()->subMonths(2),
        ]);
        Delivery::factory()->pending(5)->for($other)->create();

        $response = $this->actingAs($vendor)->getJson(
            '/reports/data?type=deliveries&from='.now()->subWeek()->toDateString().'&to='.now()->toDateString(),
        )->assertOk();

        $this->assertCount(3, $response->json('rows'));
        $this->assertSame(3, $response->json('totals.deliveries'));
        $this->assertSame(1, $response->json('totals.accepted'));
        $this->assertSame(1, $response->json('totals.pending'));
        $this->assertSame(1, $response->json('totals.rejected'));
        $this->assertEqualsWithDelta(30.0, (float) $response->json('totals.total_expected_kg'), 0.01);
        $this->assertEqualsWithDelta(12.0, (float) $response->json('totals.total_received_kg'), 0.01);
        $this->assertSame('Lingayen Aquafarm', $response->json('rows.0.1'));
    }

    /** 12.5 — the printed report tells a story, so it needs records not rows. */
    public function test_the_assessment_report_carries_narrative_records_and_a_reference(): void
    {
        $vendor = User::factory()->vendor()->create();

        $vendor->inventoryItems()->create([
            'name' => 'Tuna',
            'species' => 'Thunnus',
            'quantity' => 12.5,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        Scan::factory()->create([
            'user_id' => $vendor->id,
            'species' => 'Tuna',
            'species_confidence' => 0.71,
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
            'created_at' => now()->subDays(2),
        ]);
        /* A second scan of the same stock must not weigh the item twice. */
        Scan::factory()->create([
            'user_id' => $vendor->id,
            'species' => 'Tuna',
            'species_confidence' => 0.93,
            'freshness' => 'moderate',
            'freshness_percentage' => 74,
            'created_at' => now()->subDay(),
        ]);
        /* Nothing stocked under this name, so no quantity can be quoted. */
        Scan::factory()->create([
            'user_id' => $vendor->id,
            'species' => 'Milkfish',
            'species_confidence' => 0.88,
            'freshness' => 'fresh',
            'created_at' => now()->subHours(3),
        ]);

        $response = $this->actingAs($vendor)->getJson(
            '/reports/data?type=assessment&from='.now()->subWeek()->toDateString().'&to='.now()->toDateString(),
        )->assertOk();

        $this->assertCount(3, $response->json('records'));
        $this->assertMatchesRegularExpression(
            '/^FDA-\d{8}-[A-Z0-9]{6}$/',
            (string) $response->json('meta.reference'),
        );
        $this->assertSame($vendor->name, $response->json('meta.generated_by'));
        $this->assertSame('Local Vendor', $response->json('meta.role'));

        $tuna = collect($response->json('records'))->where('fish', 'Tuna');

        /* Records run newest first; both quote the same stocked weight and the
           confidence of the latest scan, whichever way the model felt then. */
        $this->assertSame(['Moderate', 'Fresh'], $tuna->pluck('classification')->values()->all());
        $this->assertEqualsWithDelta(0.93, (float) $tuna->first()['confidence'], 0.001);
        $this->assertEqualsWithDelta(12.5, (float) $tuna->first()['quantity'], 0.01);
        $this->assertEqualsWithDelta(12.5, (float) $tuna->last()['quantity'], 0.01);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', (string) $tuna->first()['datetime']);

        $milkfish = collect($response->json('records'))->firstWhere('fish', 'Milkfish');
        $this->assertSame('Fresh', $milkfish['classification']);
        $this->assertNull($milkfish['quantity']);

        $this->assertEqualsWithDelta(12.5, (float) $response->json('totals.total_quantity'), 0.01);
    }

    /** The inventory narrative quotes the confidence its label came from. */
    public function test_the_inventory_report_quotes_the_newest_model_confidence(): void
    {
        $vendor = User::factory()->vendor()->create();

        $vendor->inventoryItems()->create([
            'name' => 'Tuna',
            'species' => 'Thunnus',
            'quantity' => 20,
            'freshness' => 'fresh',
            'freshness_percentage' => 88,
            'last_scanned_at' => now()->subDay(),
        ]);
        $vendor->inventoryItems()->create([
            'name' => 'Bangus',
            'species' => 'Chanos chanos',
            'quantity' => 5,
            'freshness' => 'moderate',
            'freshness_percentage' => 70,
            'last_scanned_at' => now()->subDay(),
        ]);

        Scan::factory()->create([
            'user_id' => $vendor->id,
            'species' => 'Tuna',
            'species_confidence' => 0.62,
            'created_at' => now()->subDays(5),
        ]);
        Scan::factory()->create([
            'user_id' => $vendor->id,
            'species' => 'Tuna',
            'species_confidence' => 0.94,
            'created_at' => now()->subDay(),
        ]);
        /* Another vendor's scan never answers for this stock. */
        Scan::factory()->create([
            'user_id' => User::factory()->vendor()->create()->id,
            'species' => 'Bangus',
            'species_confidence' => 0.99,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($vendor)->getJson('/reports/data?type=inventory')->assertOk();

        $records = collect($response->json('records'));
        $tuna = $records->firstWhere('fish', 'Tuna');

        $this->assertSame('Fresh', $tuna['classification']);
        $this->assertEqualsWithDelta(0.94, (float) $tuna['confidence'], 0.001);
        $this->assertNull($records->firstWhere('fish', 'Bangus')['confidence']);
        $this->assertEqualsWithDelta(25.0, (float) $response->json('totals.total_quantity'), 0.01);
    }

    /** Delivery records tell the supplier, the verdict and what arrived. */
    public function test_the_delivery_records_quote_the_supplier_and_the_recorded_quantity(): void
    {
        $vendor = User::factory()->vendor()->create();
        $supplier = Supplier::factory()->for($vendor)->create(['name' => 'Lingayen Aquafarm']);

        Delivery::factory()->accepted(12)->for($vendor)->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Tuna',
            'delivery_date' => now()->subDays(2),
        ]);
        Delivery::factory()->pending(8)->for($vendor)->create([
            'supplier_id' => $supplier->id,
            'fish_type' => 'Milkfish',
            'delivery_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($vendor)->getJson(
            '/reports/data?type=deliveries&from='.now()->subWeek()->toDateString().'&to='.now()->toDateString(),
        )->assertOk();

        $records = collect($response->json('records'));
        $tuna = $records->firstWhere('fish', 'Tuna');

        $this->assertSame('Accepted', $tuna['classification']);
        $this->assertSame('Lingayen Aquafarm', $tuna['detail']);
        $this->assertEqualsWithDelta(12.0, (float) $tuna['quantity'], 0.01);

        $pending = $records->firstWhere('fish', 'Milkfish');
        $this->assertSame('Pending', $pending['classification']);
        $this->assertEqualsWithDelta(8.0, (float) $pending['quantity'], 0.01);

        /* The summary quotes what every record quotes: arrived plus promised. */
        $this->assertEqualsWithDelta(20.0, (float) $response->json('totals.total_quantity'), 0.01);
        $this->assertEqualsWithDelta(12.0, (float) $response->json('totals.total_received_kg'), 0.01);
    }

    /** The CSV streams real rows plus a totals block, formula-safe. */
    public function test_the_csv_download_streams_the_requested_report(): void
    {
        $vendor = User::factory()->vendor()->create();
        InventoryItem::factory()->for($vendor)->create([
            'name' => '=HYPERLINK("evil")',
            'species' => 'Tuna',
            'quantity' => 5,
            'min_stock' => null,
        ]);

        $response = $this->actingAs($vendor)->get('/reports/export/csv?type=inventory');

        $response->assertOk();
        $this->assertStringContainsString(
            'freshda-inventory-report-',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringContainsString(
            'text/csv',
            (string) $response->headers->get('content-type'),
        );

        $csv = (string) $response->streamedContent();

        $this->assertStringContainsString('Item,Species', $csv);
        $this->assertStringContainsString('Quantity (kg)', $csv);
        $this->assertStringContainsString('Tuna', $csv);
        /* The leading = is neutralised so Excel cannot execute it. */
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('Totals', $csv);
        $this->assertStringContainsString('Total Kg', $csv);
    }

    /** The page, its sidebar link and its export buttons all line up. */
    public function test_the_reports_page_is_wired_into_the_dashboard(): void
    {
        $vendor = User::factory()->vendor()->create();

        $this->actingAs($vendor)->get('/dashboard')
            ->assertOk()
            ->assertSee('data-page="reports"', false)
            ->assertSee('id="reports-section"', false)
            ->assertSee(route('reports.export.csv'), false)
            ->assertSee(route('reports.data'), false);

        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('id="reports-section"', false)
            ->assertDontSee('data-page="reports"', false);
    }

    /**
     * One card carries the picker and the range instead of four cards, and the
     * PDF is typeset from the narrative records the JSON endpoint returns.
     */
    public function test_the_reports_page_offers_a_single_picker_for_both_exports(): void
    {
        $vendor = User::factory()->vendor()->create();

        $html = (string) $this->actingAs($vendor)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="reportType"', $html);
        $this->assertStringContainsString('value="assessment"', $html);
        $this->assertStringContainsString('value="deliveries"', $html);
        $this->assertStringContainsString('id="reportFrom"', $html);
        $this->assertStringContainsString('id="reportTo"', $html);
        $this->assertStringContainsString('id="reportRemarks"', $html);
        /* Exactly one CSV button and one PDF button, both under the picker. */
        $this->assertSame(1, substr_count($html, 'data-csv-url='));
        $this->assertSame(2, substr_count($html, 'data-json-url='));
        /* The old per-report cards, with their own date inputs, are gone. */
        $this->assertStringNotContainsString('assessmentFrom', $html);
        $this->assertStringNotContainsString('deliveriesTo', $html);
    }

    /** The printed PDF is the narrative report, not a bare table dump. */
    public function test_the_pdf_export_typesets_the_narrative_report(): void
    {
        $script = $this->dashboardScript();

        $this->assertStringContainsString('function renderReportPdf', $script);
        $this->assertStringContainsString('"Fish Inventory and Freshness Assessment Report"', $script);
        $this->assertStringContainsString('sectionHeading("Report Summary")', $script);
        $this->assertStringContainsString('sectionHeading("Report Remarks")', $script);
        $this->assertStringContainsString('sectionHeading("System Notice")', $script);
        $this->assertStringContainsString('The fish was identified as ', $script);
        $this->assertStringContainsString('with a model confidence score of ', $script);
        $this->assertStringContainsString('"A quantity of"', $script);
        $this->assertStringContainsString('should not be considered a substitute for laboratory testing', $script);
        /* The remarks typed on the card are what the report prints. */
        $this->assertStringContainsString('getElementById("reportRemarks")', $script);
    }
}

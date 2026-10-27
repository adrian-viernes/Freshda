<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_history_renders_the_export_card_and_buttons(): void
    {
        $user = User::factory()->vendor()->create();

        $html = (string) $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringContainsString('id="exportCsvBtn"', $html);
        $this->assertStringContainsString('id="exportPdfBtn"', $html);
        $this->assertStringContainsString(route('history.export.csv'), $html);
        $this->assertStringContainsString(route('history.export.data'), $html);
    }

    /**
     * Customers get no export card at all, and the endpoints reject them even
     * if they call the URLs directly.
     */
    public function test_customers_cannot_see_or_use_the_export_controls(): void
    {
        $user = User::factory()->create();

        $html = (string) $this->actingAs($user)->get('/dashboard')->getContent();

        $this->assertStringNotContainsString('id="exportCsvBtn"', $html);
        $this->assertStringNotContainsString('id="exportPdfBtn"', $html);
        $this->assertStringNotContainsString('id="exportCard"', $html);

        $this->actingAs($user)->get(route('history.export.csv'))->assertForbidden();
        $this->actingAs($user)->get(route('history.export.data'))->assertForbidden();
    }

    public function test_csv_export_downloads_the_scan_history(): void
    {
        $user = User::factory()->vendor()->create();

        Scan::factory()->create([
            'user_id' => $user->id,
            'species' => 'Milkfish',
            'species_confidence' => 0.94,
            'freshness' => 'fresh',
            'freshness_percentage' => 94,
            'created_at' => now()->parse('2025-04-10 10:23'),
        ]);

        Scan::factory()->create([
            'user_id' => $user->id,
            'species' => 'Mackerel',
            'species_confidence' => 0.45,
            'freshness' => 'not_fresh',
            'freshness_percentage' => 45,
            'created_at' => now()->parse('2025-04-09 14:20'),
        ]);

        $response = $this->actingAs($user)->get(route('history.export.csv'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $disposition = (string) $response->baseResponse->headers->get('content-disposition');
        $this->assertStringStartsWith('attachment; filename=freshda-scan-history-', $disposition);
        $this->assertStringEndsWith('.csv', $disposition);

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Fish,Species,Freshness,"Freshness %",Confidence,Date', $content);
        $this->assertStringContainsString('Milkfish,"Chanos chanos",Fresh,94,0.94,"2025-04-10 10:23"', $content);
        $this->assertStringContainsString('Mackerel,"Scomber scombrus","Not Fresh",45,0.45,"2025-04-09 14:20"', $content);
    }

    /**
     * Batch species come straight from the browser, so a formula-looking
     * name must be neutralised before a spreadsheet executes it.
     */
    public function test_csv_export_neutralises_formula_injection(): void
    {
        $user = User::factory()->vendor()->create();

        Scan::factory()->create([
            'user_id' => $user->id,
            'species' => '=HYPERLINK("http://evil.example")',
            'freshness' => 'fresh',
            'freshness_percentage' => 90,
        ]);

        $content = $this->actingAs($user)->get(route('history.export.csv'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringNotContainsString(',"=HYPERLINK', $content);
    }

    public function test_csv_export_respects_the_freshness_filter(): void
    {
        $user = User::factory()->vendor()->create();

        Scan::factory()->create([
            'user_id' => $user->id,
            'species' => 'Milkfish',
            'freshness' => 'fresh',
            'freshness_percentage' => 94,
        ]);

        Scan::factory()->create([
            'user_id' => $user->id,
            'species' => 'Tilapia',
            'freshness' => 'moderate',
            'freshness_percentage' => 78,
        ]);

        $response = $this->actingAs($user)->get(route('history.export.csv', ['filter' => 'moderate']));

        $content = $response->streamedContent();

        $this->assertStringContainsString('Tilapia', $content);
        $this->assertStringNotContainsString('Milkfish', $content);
    }

    public function test_export_data_endpoint_returns_user_summary_and_scans(): void
    {
        $user = User::factory()->vendor()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@freshda.ph',
        ]);

        Scan::factory()->count(2)->create([
            'user_id' => $user->id,
            'freshness' => 'fresh',
        ]);

        Scan::factory()->create([
            'user_id' => $user->id,
            'freshness' => 'moderate',
        ]);

        $response = $this->actingAs($user)->get(route('history.export.data'));

        $response->assertOk()
            ->assertJsonPath('user.name', 'Juan Dela Cruz')
            ->assertJsonPath('user.email', 'juan@freshda.ph')
            ->assertJsonPath('user.role', 'Local Vendor')
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('summary.fresh', 2)
            ->assertJsonPath('summary.moderate', 1)
            ->assertJsonPath('summary.not_fresh', 0)
            ->assertJsonCount(3, 'scans')
            ->assertJsonStructure([
                'user' => ['name', 'email', 'role', 'member_since'],
                'summary' => ['total', 'fresh', 'moderate', 'not_fresh'],
                'scans' => [['fish', 'species', 'freshness', 'percentage', 'confidence', 'date']],
            ]);
    }

    public function test_export_only_includes_the_authenticated_users_scans(): void
    {
        $user = User::factory()->vendor()->create();
        $other = User::factory()->vendor()->create();

        Scan::factory()->create(['user_id' => $user->id, 'species' => 'Salmon']);
        Scan::factory()->create(['user_id' => $other->id, 'species' => 'Tuna']);

        $response = $this->actingAs($user)->get(route('history.export.data'));

        $response->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('scans.0.fish', 'Salmon');
    }

    public function test_guests_cannot_export_scan_history(): void
    {
        $this->get(route('history.export.csv'))->assertRedirect(route('login'));
        $this->get(route('history.export.data'))->assertRedirect(route('login'));
    }
}

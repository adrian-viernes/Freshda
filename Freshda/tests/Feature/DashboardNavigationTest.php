<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every navigation target in the rendered dashboard must have a section to
     * show, otherwise the click silently does nothing.
     */
    public function test_every_navigation_target_has_a_matching_section(): void
    {
        foreach (['vendor', 'customer'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

            preg_match_all('/data-page="([a-z]+)"/', $html, $matches);

            $targets = array_values(array_unique(array_diff($matches[1], ['logout'])));

            $this->assertNotEmpty($targets, "No navigation targets rendered for {$role}");

            foreach ($targets as $target) {
                $this->assertStringContainsString(
                    'id="'.$target.'-section"',
                    $html,
                    "The {$role} dashboard links to \"{$target}\" but renders no {$target} section",
                );
            }
        }
    }

    /**
     * The topbar bell goes straight to the notifications page; the old
     * slide-over panel is gone from both the markup and the script.
     */
    public function test_the_topbar_bell_opens_the_notifications_page(): void
    {
        $user = User::factory()->create();
        $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="notifBtn"', $html);
        $this->assertStringNotContainsString('id="notifPanel"', $html);

        $script = $this->dashboardScript();

        $this->assertMatchesRegularExpression(
            '/notifBtn\.addEventListener\("click", function \(\) \{\s*showPage\("notifications"\);\s*\}\);/',
            $script,
        );
        $this->assertStringNotContainsString('renderNotifications', $script);
        $this->assertStringNotContainsString('notifPanel', $script);
    }

    /**
     * The topbar avatar opens a menu whose entries jump straight to the
     * profile page or sign the user out.
     */
    public function test_the_topbar_profile_menu_offers_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="topbarUserBtn"', $html);
        $this->assertStringContainsString('id="topbarUserDropdown"', $html);
        $this->assertStringContainsString('data-page="profile"', $html);
        $this->assertStringContainsString('data-page="logout"', $html);

        $script = $this->dashboardScript();

        $this->assertStringContainsString('function closeTopbarUserDropdown', $script);
        $this->assertStringContainsString(
            'topbarUserDropdown.classList.toggle("hidden", !willOpen)',
            $script,
        );

        /* Panels that stop propagation must dismiss the menu themselves, and
           Escape has to close it along with the other floating surfaces. The
           old slide-over settings panel (a never-opened legacy surface) is
           gone, so one call site fewer than before remains. */
        $this->assertGreaterThanOrEqual(
            5,
            substr_count($script, 'closeTopbarUserDropdown();'),
        );
    }

    /**
     * The mobile bottom bar keeps Scan centred: vendors get a dedicated
     * Inventory tab, customers a Notifications tab. Neither role has a "More"
     * sheet any more - every other page is reachable from the topbar.
     */
    public function test_the_mobile_bottom_bar_slots(): void
    {
        $customer = User::factory()->create();
        $customerHtml = (string) $this->actingAs($customer)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-page="notifications"', $customerHtml);
        $this->assertStringNotContainsString('data-page="feedback"', $customerHtml);
        $this->assertStringNotContainsString('id="moreSheet"', $customerHtml);
        $this->assertStringNotContainsString('more-nav', $customerHtml);
        $this->assertStringNotContainsString('data-page="inventory"', $customerHtml);

        $vendor = User::factory()->vendor()->create();
        $vendorHtml = (string) $this->actingAs($vendor)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('data-page="inventory"', $vendorHtml);
        $this->assertStringNotContainsString('id="moreSheet"', $vendorHtml);
        $this->assertStringNotContainsString('more-nav', $vendorHtml);

        /* Scan sits in the middle slot of the five-item bottom bar. */
        preg_match_all('/class="mobile-nav-item[^"]*" data-page="([a-z]+)"/', $vendorHtml, $vendorMatches);
        $this->assertSame(['dashboard', 'batch', 'scan', 'history', 'inventory'], $vendorMatches[1]);

        preg_match_all('/class="mobile-nav-item[^"]*" data-page="([a-z]+)"/', $customerHtml, $customerMatches);
        $this->assertSame(['dashboard', 'batch', 'scan', 'history', 'notifications'], $customerMatches[1]);

        $css = $this->dashboardStylesheet();

        $this->assertSame(
            0,
            preg_match('/\.more-(sheet|nav)/', $css),
            'The removed More sheet still has styles in the stylesheet',
        );
    }

    /**
     * The dashboard header chip reads "Today" and carries the date it stands
     * for: the blade paints one straight away, the script then re-dates it to
     * the viewer's own clock (the server runs on UTC).
     */
    public function test_the_dashboard_today_chip_shows_the_current_date(): void
    {
        $user = User::factory()->create();
        $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('class="filter-chip"', $html);
        $this->assertStringContainsString('ri-calendar-line"></i> Today', $html);

        $pos = strpos($html, 'id="todayDate"');
        $this->assertNotFalse($pos, 'The dashboard header has nowhere to show the date');
        /* e.g. "Wed, Oct 22, 2026" right after the chip's Today. */
        $this->assertMatchesRegularExpression(
            '/[A-Z][a-z]{2}, [A-Z][a-z]{2} \d{1,2}, \d{4}/',
            substr($html, $pos, 90),
        );

        $script = $this->dashboardScript();

        $this->assertStringContainsString('getElementById("todayDate")', $script);
        $this->assertStringContainsString('toLocaleDateString', $script);
    }

    /**
     * The dashboard ships as ordered modules (the project has no build step),
     * so the layout has to emit every file from config/assets.php in exactly
     * that order: a missing or reordered module changes what the page can run.
     */
    public function test_the_dashboard_loads_every_asset_module_in_config_order(): void
    {
        $user = User::factory()->create();
        $html = (string) $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();

        foreach (['js', 'css'] as $type) {
            $previous = -1;

            foreach ((array) config("assets.dashboard.{$type}") as $module) {
                $needle = "{$type}/dashboard/{$module}?v=";
                $pos = strpos($html, $needle);

                $this->assertNotFalse($pos, "The dashboard never loads {$needle}");
                $this->assertGreaterThan($previous, $pos, "{$module} is loaded out of order");
                $this->assertFileExists(public_path("{$type}/dashboard/{$module}"));

                $previous = $pos;
            }
        }

        /* The old monoliths must stay gone, or both would be shipped. */
        $this->assertFileDoesNotExist(public_path('js/dashboard.js'));
        $this->assertFileDoesNotExist(public_path('css/dashboard.css'));
        $this->assertStringNotContainsString('js/dashboard.js', $html);
        $this->assertStringNotContainsString('css/dashboard.css', $html);
    }

    /**
     * API failures must surface the server's message instead of always
     * reporting success, and local-only notifications never hit the API
     * (their ids only exist in the browser).
     */
    public function test_the_script_checks_http_status_and_skips_local_notifications(): void
    {
        $script = $this->dashboardScript();

        $this->assertStringContainsString('function serverErrorMessage(r, data)', $script);
        $this->assertStringContainsString('if (!r.ok) throw new Error(serverErrorMessage(r, data));', $script);
        $this->assertStringContainsString('function markNotificationRead(notif)', $script);
        $this->assertStringContainsString('function deleteNotification(notif)', $script);
        $this->assertStringContainsString('if (!notif.local)', $script);
        $this->assertStringContainsString('local: true', $script);
    }
}

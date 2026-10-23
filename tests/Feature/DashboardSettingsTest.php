<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_preference_control_maps_to_a_known_preference(): void
    {
        $script = $this->script();
        $html = (string) $this->actingAs(User::factory()->create())->get('/dashboard')->getContent();

        preg_match('/var DEFAULT_SETTINGS = \{(.*?)\n      \};/s', $script, $matches);
        $this->assertNotEmpty($matches, 'DEFAULT_SETTINGS block is missing from the dashboard script');

        preg_match_all('/^\s*([a-zA-Z]+):/m', $matches[1], $keys);
        $preferences = $keys[1];

        $this->assertNotEmpty($preferences, 'No preferences found in DEFAULT_SETTINGS');

        /* Every control in the rendered page must reference a real preference. */
        preg_match_all('/data-setting-(?:checkbox|select|toggle)="([^"]+)"/', $html, $controls);
        $this->assertNotEmpty($controls[1], 'No preference controls were rendered');

        $unknown = array_values(array_diff($controls[1], $preferences));
        $this->assertEmpty(
            $unknown,
            'These preference controls do not exist in DEFAULT_SETTINGS: '.implode(', ', $unknown),
        );

        /* Every preference must be reachable from a control. */
        $missing = array_values(array_diff($preferences, $controls[1]));
        $this->assertEmpty(
            $missing,
            'These preferences have no control in the rendered page: '.implode(', ', $missing),
        );
    }

    public function test_every_translated_string_exists_in_every_dictionary(): void
    {
        $script = $this->script();

        preg_match('/var I18N = \{.*?\n      \};/s', $script, $block);
        $this->assertNotEmpty($block, 'I18N dictionary is missing from the dashboard script');

        $parts = preg_split('/\n        (en|tl): \{/', $block[0], -1, PREG_SPLIT_DELIM_CAPTURE);
        $this->assertNotFalse($parts, 'Could not split the I18N dictionaries');

        $dictionaries = [];
        for ($i = 1; $i + 1 < count($parts); $i += 2) {
            preg_match_all('/^\s*"([^"]+)":/m', $parts[$i + 1], $found);
            $dictionaries[$parts[$i]] = $found[1];
        }

        $this->assertArrayHasKey('en', $dictionaries, 'English dictionary is missing');
        $this->assertArrayHasKey('tl', $dictionaries, 'Tagalog dictionary is missing');
        $this->assertNotEmpty($dictionaries['en'], 'English dictionary is empty');
        $this->assertNotEmpty($dictionaries['tl'], 'Tagalog dictionary is empty');

        $missingInTl = array_diff($dictionaries['en'], $dictionaries['tl']);
        $this->assertEmpty(
            $missingInTl,
            'Tagalog dictionary is missing keys: '.implode(', ', $missingInTl),
        );

        $extraInTl = array_diff($dictionaries['tl'], $dictionaries['en']);
        $this->assertEmpty(
            $extraInTl,
            'Tagalog dictionary has keys that English does not: '.implode(', ', $extraInTl),
        );
    }

    public function test_settings_navigation_targets_the_page_not_the_panel(): void
    {
        $script = $this->script();

        $this->assertStringContainsString('"settings",', $script);
        $this->assertStringContainsString('if (pageId !== "scan") stopCameraStream();', $script);
        $this->assertStringNotContainsString('openSettingsFromPageBtn', $script);
    }

    private function script(): string
    {
        return $this->dashboardScript();
    }
}

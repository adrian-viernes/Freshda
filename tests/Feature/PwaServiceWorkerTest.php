<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The service worker shares one browser cache between users of the same
 * device, so it must never store authenticated markup under a public URL.
 */
class PwaServiceWorkerTest extends TestCase
{
    public function test_the_cache_only_stores_plain_non_redirected_responses(): void
    {
        $sw = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('function isStorable(response)', $sw);
        /* /login sits behind the guest middleware; following that redirect
           would cache the dashboard HTML under the /login key. */
        $this->assertStringContainsString('if (response.redirected)', $sw);
        $this->assertStringContainsString('response.headers.get("Cache-Control")', $sw);
        $this->assertStringContainsString('no-store', $sw);
    }

    public function test_only_public_pages_are_precached(): void
    {
        $sw = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('var CORE_ASSETS = ["/", "/scan", "/manifest.json"]', $sw);
        $this->assertStringContainsString('var PUBLIC_NAVIGATIONS = ["/", "/scan", "/login"]', $sw);
    }

    /** Offline visitors get a small notice, never a stale copy of a page. */
    public function test_offline_navigations_get_a_fresh_offline_page(): void
    {
        $sw = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('function offlineResponse()', $sw);
        $this->assertStringContainsString('return cached || offlineResponse();', $sw);
        $this->assertStringNotContainsString('return cached || caches.match("/");', $sw);
    }

    /** Every cache version bump purges entries written by older releases. */
    public function test_the_cache_version_is_bumped_and_old_caches_are_deleted(): void
    {
        $sw = (string) file_get_contents(public_path('sw.js'));

        $this->assertMatchesRegularExpression('/var CACHE_NAME = "freshda-v\d+";/', $sw);
        $this->assertStringContainsString('caches.delete(key)', $sw);
    }

    /** Long-lived tabs must pick up a new worker instead of running old code. */
    public function test_the_layout_registers_the_worker_with_an_update_flow(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString("updateViaCache: 'none'", $layout);
        $this->assertStringContainsString('controllerchange', $layout);
        $this->assertStringContainsString('window.location.reload()', $layout);
    }
}

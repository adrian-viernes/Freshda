<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The dashboard script exactly as the browser receives it: every module
     * from config/assets.php concatenated in load order. Behaviour tests keep
     * asserting on one string while the code itself ships in small files.
     */
    protected function dashboardScript(): string
    {
        return $this->dashboardAsset('js');
    }

    /**
     * The dashboard stylesheet, concatenated in load order (config/assets.php).
     */
    protected function dashboardStylesheet(): string
    {
        return $this->dashboardAsset('css');
    }

    /**
     * @param  string  $type  Asset type: "js" or "css".
     */
    private function dashboardAsset(string $type): string
    {
        $bundle = '';

        foreach ((array) config("assets.dashboard.{$type}") as $module) {
            $path = public_path("{$type}/dashboard/{$module}");
            $this->assertFileExists($path);

            $bundle .= (string) file_get_contents($path);
        }

        return $bundle;
    }
}

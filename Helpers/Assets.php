<?php

namespace Coachview\Helpers;

use Coachview\Constants;

class Assets
{
    public static function enqueueScript(string $name, string $path, array $deps = [], $version = '1.1'): void {
        wp_enqueue_script($name, self::toPath($path), $deps, $version);
    }

    public static function enqueueStyle(string $name, string $path, array $deps = [], $version = '1.1', string $media = 'all'): void {
        wp_enqueue_style($name, self::toPath($path), $deps, $version, $media);
    }

    public static function toPath(string $path = ''): string {
        return plugin_dir_url(__FILE__) . '../' . Constants::ASSETS_BASE_DIR . '/' . ltrim($path, '/');
    }

}

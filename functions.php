<?php

use Coachview\Constants;


function cv_assets_url(string $path = ''): string {
    return plugin_dir_url(__FILE__) . Constants::ASSETS_BASE_DIR . '/' . ltrim($path, '/');
}

function wp_get_query_var( $key, $default = '' ) {
    if ( isset( $_GET[ $key ] ) ) {
        return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
    }
    return $default;
}

function normalize_enums(mixed $data): mixed
{
    // Enum → scalar
    if ($data instanceof \BackedEnum) {
        return $data->value;
    }

    if ($data instanceof \UnitEnum) {
        return $data->name;
    }

    // Object → associative array copy
    if (is_object($data)) {
        $out = [];
        foreach (get_object_vars($data) as $k => $v) {
            $out[$k] = normalize_enums($v);
        }
        return $out;
    }

    // Array → normalized array
    if (is_array($data)) {
        return array_map(fn($v) => normalize_enums($v), $data);
    }

    return $data;
}
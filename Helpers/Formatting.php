<?php

namespace Coachview\Helpers;

class Formatting
{
    public static function displayDate(int $timestamp): string
    {
        $now = time();
        if (date('Y', $timestamp) !== date('Y', $now)) {
            return wp_date('j F Y', $timestamp);
        }
        return wp_date('j F', $timestamp);
    }
}

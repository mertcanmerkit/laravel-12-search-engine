<?php

namespace App\Support;

final class DurationParser
{

    /**
     *
     * Converts duration strings to seconds.
     *
     * Supported formats:
     * - "HH:MM:SS" (hours 0–999, minutes/seconds 0–59)
     * - "MM:SS" (minutes up to 99999, seconds 0–59)
     *
     */
    public static function toSeconds(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // HH:MM:SS
        if (preg_match('/^(?<h>\d{1,3}):(?<m>\d{1,2}):(?<s>\d{2})$/', $value, $m)) {
            $h = (int)$m['h'];
            $mm = (int)$m['m'];
            $ss = (int)$m['s'];
            if ($mm >= 60 || $ss >= 60) {
                return null;
            }
            return $h * 3600 + $mm * 60 + $ss;
        }

        // MM:SS
        if (preg_match('/^(?<m>\d{1,5}):(?<s>\d{2})$/', $value, $m)) {
            $mm = (int)$m['m'];
            $ss = (int)$m['s'];
            if ($ss >= 60) {
                return null;
            }
            return $mm * 60 + $ss;
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Validation;

use Closure;

final class MusicScoreValidator
{
    public static function rules(bool $partial = false): array
    {
        $content = ['array', function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_array($value) || (! self::isFrontendScore($value) && ! self::isNotationScore($value))) {
                $fail('The score content has an invalid notation schema.');
            }
        }];

        return $partial
            ? ['title' => ['sometimes', 'required', 'string', 'max:255'], 'content' => ['sometimes', 'required', ...$content]]
            : ['title' => ['required', 'string', 'max:255'], 'content' => ['required', ...$content]];
    }

    private static function isFrontendScore(array $score): bool
    {
        if (($score['version'] ?? null) !== 1 || ! is_numeric($score['tempo'] ?? null)
            || ! is_int($score['beatsPerBar'] ?? null) || ! in_array($score['beatUnit'] ?? null, [2, 4, 8], true)
            || ! is_array($score['tracks'] ?? null) || $score['tempo'] < 30 || $score['tempo'] > 240
            || $score['beatsPerBar'] < 1 || $score['beatsPerBar'] > 12) {
            return false;
        }

        foreach ($score['tracks'] as $track) {
            if (! is_array($track) || ! is_string($track['id'] ?? null) || ! is_string($track['name'] ?? null) || ! is_array($track['notes'] ?? null)) {
                return false;
            }
            foreach ($track['notes'] as $note) {
                if (! is_array($note) || ! is_string($note['id'] ?? null) || ! is_string($note['pitch'] ?? null)
                    || ! is_int($note['octave'] ?? null) || ! is_numeric($note['start'] ?? null)
                    || ! in_array($note['duration'] ?? null, [0.25, 0.5, 1, 2], true)
                    || ($note['velocity'] ?? 0.8) < 0 || ($note['velocity'] ?? 0.8) > 1) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function isNotationScore(array $score): bool
    {
        if (($score['schema_version'] ?? null) !== 1 || ! is_array($score['layout'] ?? null)
            || ! is_array($score['time'] ?? null) || ! is_numeric($score['tempo'] ?? null)
            || ! is_array($score['staves'] ?? null) || $score['tempo'] < 30 || $score['tempo'] > 240) {
            return false;
        }

        $beats = $score['time']['beats'] ?? null;
        $unit = $score['time']['beat_unit'] ?? null;
        if (! is_int($beats) || $beats < 1 || $beats > 12 || ! in_array($unit, [2, 4, 8], true)) {
            return false;
        }

        foreach ($score['staves'] as $staff) {
            if (! is_array($staff) || ! is_string($staff['id'] ?? null) || ! is_array($staff['measures'] ?? null)) {
                return false;
            }
            foreach ($staff['measures'] as $measure) {
                if (! is_array($measure) || ! is_array($measure['elements'] ?? null)) {
                    return false;
                }
                $sum = 0.0;
                foreach ($measure['elements'] as $element) {
                    if (! is_array($element) || ! in_array($element['duration'] ?? null, [0.25, 0.5, 1, 2, 4], true)) {
                        return false;
                    }
                    $sum += (float) $element['duration'];
                    if (isset($element['pitch']) && (! is_string($element['pitch']) || ! preg_match('/^[A-G](?:#|b)?[0-9]$/', $element['pitch']))) {
                        return false;
                    }
                }
                if (abs($sum - $beats) > 0.00001) {
                    return false;
                }
            }
        }

        return true;
    }
}

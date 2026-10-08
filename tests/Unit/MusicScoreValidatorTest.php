<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\MusicScoreValidator;
use Tests\TestCase;

final class MusicScoreValidatorTest extends TestCase
{
    public function test_store_rules_require_title_and_json_content(): void
    {
        self::assertSame(['required', 'string', 'max:255'], MusicScoreValidator::rules()['title']);
        self::assertSame('required', MusicScoreValidator::rules()['content'][0]);
        self::assertSame('array', MusicScoreValidator::rules()['content'][1]);
    }

    public function test_update_rules_are_partial(): void
    {
        self::assertSame('sometimes', MusicScoreValidator::rules(true)['content'][0]);
    }

    public function test_rules_reject_invalid_content_schema(): void
    {
        $rules = MusicScoreValidator::rules()['content'];
        $validator = validator(['content' => ['version' => 1]], ['content' => $rules]);

        self::assertTrue($validator->fails());
    }

    public function test_rules_accept_current_frontend_schema(): void
    {
        $content = [
            'version' => 1,
            'tempo' => 100,
            'beatsPerBar' => 4,
            'beatUnit' => 4,
            'tracks' => [[
                'id' => 'track-1',
                'name' => 'Piano',
                'notes' => [[
                    'id' => 'note-1',
                    'pitch' => 'C',
                    'octave' => 4,
                    'start' => 0,
                    'duration' => 1,
                ]],
            ]],
        ];
        $validator = validator(['content' => $content], ['content' => MusicScoreValidator::rules()['content']]);

        self::assertFalse($validator->fails());
    }

    public function test_rules_accept_notation_schema_when_measures_sum_to_time_signature(): void
    {
        $content = [
            'schema_version' => 1,
            'layout' => ['page' => 'portrait'],
            'time' => ['beats' => 4, 'beat_unit' => 4],
            'tempo' => 100,
            'staves' => [[
                'id' => 'staff-1',
                'measures' => [['elements' => [
                    ['duration' => 1, 'pitch' => 'C4'],
                    ['duration' => 1, 'pitch' => 'D4'],
                    ['duration' => 2, 'pitch' => 'E4'],
                ]]],
            ]],
        ];
        $validator = validator(['content' => $content], ['content' => MusicScoreValidator::rules()['content']]);

        self::assertFalse($validator->fails());
    }

    public function test_rules_reject_measure_duration_mismatch(): void
    {
        $content = [
            'schema_version' => 1, 'layout' => [], 'time' => ['beats' => 4, 'beat_unit' => 4], 'tempo' => 100,
            'staves' => [['id' => 'staff-1', 'measures' => [['elements' => [['duration' => 2]]]]]],
        ];
        $validator = validator(['content' => $content], ['content' => MusicScoreValidator::rules()['content']]);

        self::assertTrue($validator->fails());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MusicScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use ZipArchive;

final class MusicScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_list_owned_scores(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        MusicScore::factory()->for($user)->create();
        MusicScore::factory()->for($other)->create();

        $response = $this->withToken(JWTAuth::fromUser($user))->getJson('/api/music-scores');

        $response->assertOk()->assertJsonCount(1);
    }

    public function test_user_can_import_mxl(): void
    {
        if (! class_exists(ZipArchive::class)) {
            self::markTestSkipped('ZIP extension is not enabled.');
        }

        $user = User::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'score').'.mxl';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?><container xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="score.xml"/></rootfiles></container>');
        $zip->addFromString('score.xml', '<score-partwise><work><work-title>Test</work-title></work><part-list><score-part id="P1"><part-name>Piano</part-name></score-part></part-list><part id="P1"><measure number="1"><attributes><divisions>1</divisions><time><beats>4</beats><beat-type>4</beat-type></time></attributes><note><pitch><step>C</step><octave>4</octave></pitch><duration>1</duration></note></measure></part></score-partwise>');
        $zip->close();

        $response = $this->withToken(JWTAuth::fromUser($user))->post('/api/music-scores/import', ['file' => UploadedFile::fake()->createWithContent('score.mxl', file_get_contents($path))]);
        unlink($path);

        $response->assertOk()->assertJsonPath('data.title', 'Test')->assertJsonPath('data.content.tracks.0.notes.0.pitch', 'C');
    }

    public function test_user_can_create_score_with_json_content(): void
    {
        $user = User::factory()->create();

        $response = $this->withToken(JWTAuth::fromUser($user))->postJson('/api/music-scores', [
            'title' => 'Mi partitura',
            'content' => [
                'version' => 1,
                'tempo' => 100,
                'beatsPerBar' => 4,
                'beatUnit' => 4,
                'tracks' => [],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.user_id', $user->id);
        $this->assertDatabaseHas('music_scores', ['user_id' => $user->id, 'title' => 'Mi partitura']);
    }
}

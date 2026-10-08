<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MusicXmlImportService;
use Tests\TestCase;
use ZipArchive;

final class MusicXmlImportServiceTest extends TestCase
{
    public function test_honors_backup_and_true_durations(): void
    {
        if (! class_exists(ZipArchive::class)) {
            self::markTestSkipped('ZIP extension is not enabled.');
        }

        $path = tempnam(sys_get_temp_dir(), 'score').'.mxl';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?><container xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="score.xml"/></rootfiles></container>');
        // m1: whole rest (treble) + backup to beat 0 + two half notes (bass); m2: one half note.
        $xml = '<score-partwise><work><work-title>Backup</work-title></work><part-list><score-part id="P1"><part-name>Piano</part-name></score-part></part-list><part id="P1">'
            .'<measure number="1"><attributes><divisions>4</divisions><time><beats>4</beats><beat-type>4</beat-type></time></attributes>'
            .'<note><rest/><duration>16</duration><voice>1</voice></note>'
            .'<backup><duration>16</duration></backup>'
            .'<note><pitch><step>D</step><octave>3</octave></pitch><duration>8</duration><voice>5</voice></note>'
            .'<note><pitch><step>A</step><octave>3</octave></pitch><duration>8</duration><voice>5</voice></note>'
            .'</measure>'
            .'<measure number="2"><note><pitch><step>B</step><octave>2</octave></pitch><duration>8</duration><voice>5</voice></note></measure>'
            .'</part></score-partwise>';
        $zip->addFromString('score.xml', $xml);
        $zip->close();

        $result = (new MusicXmlImportService)->parse($path, 'Fallback');
        unlink($path);

        $notes = $result['content']['tracks'][0]['notes'];
        $this->assertCount(3, $notes);
        $this->assertSame('D', $notes[0]['pitch']);
        $this->assertSame(0.0, $notes[0]['start']);
        $this->assertSame(2.0, $notes[1]['start']);
        $this->assertSame(4.0, $notes[2]['start']);
    }
}

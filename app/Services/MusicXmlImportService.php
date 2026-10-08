<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class MusicXmlImportService
{
    public function parse(string $path, string $fallbackTitle): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZIP support is not enabled on the server.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Invalid MXL archive.');
        }

        $container = $zip->getFromName('META-INF/container.xml');
        if ($container === false) {
            $zip->close();
            throw new RuntimeException('MXL container.xml is missing.');
        }
        $containerXml = new SimpleXMLElement($container);
        $rootfilePath = $containerXml->xpath('//*[local-name()="rootfile"]/@*[local-name()="full-path"]')[0] ?? null;
        $rootfile = $rootfilePath instanceof SimpleXMLElement ? (string) $rootfilePath : '';
        if ($rootfile === '') {
            $zip->close();
            throw new RuntimeException('MXL MusicXML path is missing.');
        }
        $musicXml = $zip->getFromName($rootfile);
        $zip->close();
        if ($musicXml === false) {
            throw new RuntimeException('Referenced MusicXML file is missing.');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($musicXml);
        if (! $xml instanceof SimpleXMLElement || $xml->getName() !== 'score-partwise') {
            throw new RuntimeException('Unsupported MusicXML document.');
        }
        $workTitle = $xml->xpath('//*[local-name()="work-title"]')[0] ?? null;
        $movementTitle = $xml->xpath('//*[local-name()="movement-title"]')[0] ?? null;
        $title = trim((string) ($workTitle ?? $movementTitle ?? $fallbackTitle)) ?: $fallbackTitle;
        $tempo = 100;
        $beatsPerBar = 4;
        $beatUnit = 4;
        $tracks = [];
        $partNames = [];
        foreach ($xml->xpath('//*[local-name()="part-list"]/*[local-name()="score-part"]') ?: [] as $part) {
            $partName = $part->xpath('./*[local-name()="part-name"]')[0] ?? null;
            $partNames[(string) $part['id']] = trim((string) $partName);
        }
        foreach ($xml->xpath('//*[local-name()="part"]') ?: [] as $part) {
            $id = (string) $part['id'];
            $notes = [];
            $cursor = 0.0;
            $divisions = 1.0;
            foreach ($part->xpath('./*[local-name()="measure"]') ?: [] as $measure) {
                $attributesNode = $measure->xpath('./*[local-name()="attributes"]')[0] ?? null;
                if ($attributesNode instanceof SimpleXMLElement) {
                    $divisionsNode = $attributesNode->xpath('./*[local-name()="divisions"]')[0] ?? null;
                    if ($divisionsNode instanceof SimpleXMLElement) {
                        $divisions = max(1.0, (float) $divisionsNode);
                    }
                    $beatsNode = $attributesNode->xpath('./*[local-name()="time"]/*[local-name()="beats"]')[0] ?? null;
                    $beatTypeNode = $attributesNode->xpath('./*[local-name()="time"]/*[local-name()="beat-type"]')[0] ?? null;
                    if ($beatsNode instanceof SimpleXMLElement && $beatTypeNode instanceof SimpleXMLElement) {
                        $beatsPerBar = max(1, min(12, (int) $beatsNode));
                        $beatUnit = (int) $beatTypeNode;
                    }
                }
                foreach ($measure->xpath('./*[local-name()="direction"]/*[local-name()="sound"]') ?: [] as $sound) {
                    if (isset($sound['tempo']) && (float) $sound['tempo'] > 0) {
                        $tempo = max(30, min(240, (int) round((float) $sound['tempo'])));
                    }
                }
                foreach ($measure->xpath('./*[local-name()="note" or local-name()="backup" or local-name()="forward"]') ?: [] as $el) {
                    $tag = $el->getName();
                    if ($tag === 'backup' || $tag === 'forward') {
                        $moveNode = $el->xpath('./*[local-name()="duration"]')[0] ?? null;
                        $move = $moveNode !== null ? (float) $moveNode / $divisions : 0.0;
                        $cursor = $tag === 'backup' ? $cursor - $move : $cursor + $move;
                        continue;
                    }
                    $note = $el;
                    $durationNode = $note->xpath('./*[local-name()="duration"]')[0] ?? null;
                    $trueQuarters = $durationNode !== null ? (float) $durationNode / $divisions : 0.0;
                    $duration = $this->duration($trueQuarters);
                    $isChord = $note->xpath('./*[local-name()="chord"]') !== [];
                    $start = $isChord && $notes !== [] ? $notes[array_key_last($notes)]['start'] : $cursor;
                    if ($note->xpath('./*[local-name()="rest"]') !== []) {
                        if (! $isChord) {
                            $cursor += $trueQuarters;
                        }

                        continue;
                    }
                    $pitchNode = $note->xpath('./*[local-name()="pitch"]')[0] ?? null;
                    $stepNode = $pitchNode?->xpath('./*[local-name()="step"]')[0] ?? null;
                    $alterNode = $pitchNode?->xpath('./*[local-name()="alter"]')[0] ?? null;
                    $octaveNode = $pitchNode?->xpath('./*[local-name()="octave"]')[0] ?? null;
                    $pitch = (string) $stepNode;
                    $alter = (int) $alterNode;
                    $pitch .= $alter === 1 ? '#' : ($alter === -1 ? 'b' : '');
                    $notes[] = ['id' => $id.'-'.count($notes), 'pitch' => $pitch, 'octave' => (int) $octaveNode, 'start' => $start, 'duration' => $duration, 'velocity' => 0.8];
                    if (! $isChord) {
                        $cursor += $trueQuarters;
                    }
                }
            }
            $tracks[] = ['id' => $id ?: 'track-'.count($tracks), 'name' => $partNames[$id] ?: 'Part '.(count($tracks) + 1), 'notes' => $notes];
        }
        if ($tracks === []) {
            throw new RuntimeException('MusicXML contains no parts.');
        }
        if (! in_array($beatUnit, [2, 4, 8], true)) {
            $beatUnit = 4;
        }

        return ['title' => $title, 'content' => compact('tempo', 'beatsPerBar', 'beatUnit', 'tracks') + ['version' => 1]];
    }

    private function duration(float $quarters): float
    {
        $allowed = [0.25, 0.5, 1.0, 2.0];
        usort($allowed, static fn (float $a, float $b): int => abs($a - $quarters) <=> abs($b - $quarters));

        return $allowed[0];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class SongController extends Controller {
  protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }

  public function index(Request $request): DataSetResource {
    $filter = $request->get("filter");

    $query = queryServerSide($request, Song::query());

    if ($filter) {
      $query->where(function ($q) use ($filter) {
        $q->where("title", "like", "%" . $filter . "%")
          ->orWhere("artist", "like", "%" . $filter . "%");
      });
    }

    if ($orgId = $request->get('org_id')) {
      $query->where('org_id', $orgId);
    }

    $songs = $query->paginate($request->get('itemsPerPage'));
    return new DataSetResource($songs);
  }

  public function show(Song $song): JsonResponse {
    return response()->json($song);
  }

  public function store(Request $request): JsonResponse {
    $data = $request->all();

    // Support JSON import format: { title, sections: [...] } or { content: { sections } } with syllable->text
    $data = $this->normalizeContentInput($data);

    // org_id is now optional (songs are global). Normalize empty values to null.
    if (!isset($data['org_id']) || $data['org_id'] === '' || $data['org_id'] === 0 || $data['org_id'] === '0') {
      $data['org_id'] = null;
    }
    // Decode base64-encoded org_id if provided (allow creating on encoded id)
    if (isset($data['org_id']) && is_string($data['org_id'])) {
      $decoded = base64_decode($data['org_id'], true);
      if ($decoded !== false && is_numeric($decoded)) {
        $data['org_id'] = (int) $decoded;
      }
    }
    // Ensure null if still empty string after decode
    if (empty($data['org_id']) && $data['org_id'] !== null) {
      $data['org_id'] = null;
    }

    $validator = Validator::make($data, [
      'title' => 'required|string|max:255',
      'artist' => 'nullable|string|max:255',
      'key' => 'nullable|string|max:50',
      'tempo' => 'nullable|string|max:50',
      'content' => 'nullable|array',
      'org_id' => 'nullable|exists:organizations,id',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $data['created_by'] = $this->user->id;

    $song = Song::create($data);

    return response()->json([
      'success' => __('messa.song_create'),
      'data' => $song,
    ], 201);
  }

  public function update(Request $request, Song $song): JsonResponse {
    $data = $request->all();

    // Allow Editor JSON import format on update as well (sections at top level or syllable->text)
    $data = $this->normalizeContentInput($data);

    $validator = Validator::make($data, [
      'title' => 'sometimes|required|string|max:255',
      'artist' => 'nullable|string|max:255',
      'key' => 'nullable|string|max:50',
      'tempo' => 'nullable|string|max:50',
      'content' => 'nullable|array',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    // Never allow changing the org of an existing song via update
    if (isset($data['org_id'])) {
      unset($data['org_id']);
    }

    $song->update($data);

    return response()->json([
      'success' => __('messa.song_update'),
      'data' => $song,
    ]);
  }

  public function destroy(Song $song): JsonResponse {
    $song->delete();
    return response()->json(null, 204);
  }

  /**
   * Normalize Editor JSON import format so backend can save songs created
   * from Editor's Cargar JSON / Exportar JSON.
   *
   * Handles:
   * - Top-level { title, sections, tabs } (attached file) -> { content: { sections, tabs } }
   * - Content as JSON string
   * - Syllable alias: { syllable } -> { text }
   * - Missing ids / chords / notes defaults
   */
  private function normalizeContentInput(array $data): array {
    // Top-level sections (exported JSON) without content wrapper
    if (!isset($data['content']) && isset($data['sections']) && is_array($data['sections'])) {
      $data['content'] = [
        'sections' => $data['sections'],
        'tabs' => $data['tabs'] ?? [],
      ];
      unset($data['sections'], $data['tabs']);
    }

    // Content as JSON string (e.g. multipart or raw)
    if (isset($data['content']) && is_string($data['content'])) {
      $decoded = json_decode($data['content'], true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $data['content'] = $decoded;
      }
    }

    if (isset($data['content']) && is_array($data['content'])) {
      $data['content'] = $this->normalizeContentStructure($data['content']);
    }

    return $data;
  }

  private function normalizeContentStructure(array $content): array {
    $sections = $content['sections'] ?? [];
    $tabs = $content['tabs'] ?? [];

    $normalizedSections = [];
    foreach ((array) $sections as $sec) {
      if (!is_array($sec)) continue;
      $secName = $sec['name'] ?? 'Sección';
      $secId = $sec['id'] ?? 'sec-'.Str::random(6).Str::random(4);
      $timesRaw = $sec['times'] ?? $sec['repeat'] ?? $sec['repeats'] ?? 1;
      $times = (int) $timesRaw;
      if ($times < 1) $times = 1;
      if ($times > 20) $times = 20;
      $lines = $sec['lines'] ?? [];
      $normalizedLines = [];
      foreach ((array) $lines as $line) {
        if (!is_array($line)) continue;
        $lineId = $line['id'] ?? 'ln-'.Str::random(6).Str::random(4);
        $timesRawLine = $line['times'] ?? $line['repeat'] ?? $line['repeats'] ?? 1;
        $timesLine = (int) $timesRawLine;
        if ($timesLine < 1) $timesLine = 1;
        if ($timesLine > 20) $timesLine = 20;
        $syllables = $line['syllables'] ?? [];
        $normalizedSyllables = [];
        foreach ((array) $syllables as $syl) {
          if (!is_array($syl)) continue;
          $text = $syl['text'] ?? $syl['syllable'] ?? '';
          $chords = $syl['chords'] ?? [];
          $notes = $syl['notes'] ?? [];
          $id = $syl['id'] ?? 'sy-'.Str::random(6).Str::random(4);
          $normalizedSyllables[] = [
            'id' => (string) $id,
            'text' => (string) $text,
            'chords' => is_array($chords) ? array_values($chords) : [],
            'notes' => is_array($notes) ? array_values($notes) : [],
          ];
        }
        $normalizedLines[] = [
          'id' => (string) $lineId,
          'times' => (int) $timesLine,
          'syllables' => $normalizedSyllables,
        ];
      }
      $normalizedSections[] = [
        'id' => (string) $secId,
        'name' => (string) $secName,
        'times' => (int) $times,
        'lines' => $normalizedLines,
      ];
    }

    $normalizedTabs = [];
    foreach ((array) $tabs as $tab) {
      if (!is_array($tab)) continue;
      $normalizedTabs[] = [
        'id' => (string) ($tab['id'] ?? 'tab-'.Str::random(6).Str::random(4)),
        'title' => (string) ($tab['title'] ?? 'Tab'),
        'tablature' => (string) ($tab['tablature'] ?? $tab['content'] ?? ''),
      ];
    }

    return [
      'sections' => $normalizedSections,
      'tabs' => $normalizedTabs,
    ];
  }
}
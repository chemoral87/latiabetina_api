<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMusicScoreRequest;
use App\Http\Requests\UpdateMusicScoreRequest;
use App\Models\MusicScore;
use App\Services\MusicXmlImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Tymon\JWTAuth\Facades\JWTAuth;

final class MusicScoreController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(MusicScore::where('user_id', JWTAuth::user()->id)->latest()->get());
    }

    public function store(StoreMusicScoreRequest $request): JsonResponse
    {
        $score = MusicScore::create([...$request->validated(), 'user_id' => JWTAuth::user()->id]);

        return response()->json(['success' => __('messa.music_score_create'), 'data' => $score], 201);
    }

    public function import(Request $request, MusicXmlImportService $importer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:10240'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['mxl', 'musicxml', 'xml'], true)) {
            return response()->json(['message' => 'El archivo debe ser MusicXML (.mxl, .musicxml o .xml).'], 422);
        }

        try {
            $result = $importer->parse($request->file('file')->getRealPath(), pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $result]);
    }

    public function show(int $musicScore): JsonResponse
    {
        return response()->json($this->owned($musicScore));
    }

    public function update(UpdateMusicScoreRequest $request, int $musicScore): JsonResponse
    {
        $score = $this->owned($musicScore);
        $score->update($request->validated());

        return response()->json(['success' => __('messa.music_score_update'), 'data' => $score->fresh()]);
    }

    public function destroy(int $musicScore): JsonResponse
    {
        $this->owned($musicScore)->delete();

        return response()->json(null, 204);
    }

    private function owned(int $id): MusicScore
    {
        return MusicScore::where('user_id', JWTAuth::user()->id)->findOrFail($id);
    }
}

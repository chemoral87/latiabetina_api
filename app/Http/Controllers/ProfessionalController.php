<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfessionalController extends Controller {
  protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }

  // Public endpoint: search professionals by title or name. No auth.
  public function publicIndex(Request $request) {
    $filter = $request->get('filter');

    $query = Professional::query();

    if ($filter) {
      $query->where(function ($q) use ($filter) {
        $q->where('title', 'like', '%'.$filter.'%')
          ->orWhere('name', 'like', '%'.$filter.'%')
          ->orWhere('last_name', 'like', '%'.$filter.'%')
          ->orWhere('description', 'like', '%'.$filter.'%');
      });
    }

    $professionals = $query->orderBy('created_at', 'desc')
      ->select(['id', 'name', 'last_name', 'phone_number', 'title', 'description', 'status', 'created_at'])
      ->paginate($request->get('itemsPerPage', 20));

    return response()->json($professionals);
  }

  // Authenticated: return the current user's single listing (or null).
  public function myListing() {
    if (!$this->user) {
      return response()->json(['errors' => ['auth' => ['No autenticado']]], 401);
    }

    $professional = Professional::where('user_id', $this->user->id)->first();

    return response()->json($professional);
  }

  // Authenticated: publish a professional service listing. One listing per user.
  public function store(Request $request) {
    if (!$this->user) {
      return response()->json(['errors' => ['auth' => ['No autenticado']]], 401);
    }

    if (Professional::where('user_id', $this->user->id)->exists()) {
      return response()->json([
        'message' => 'Ya ha publicado un servicio. Solo se permite una publicación por usuario.',
      ], 409);
    }

    $data = $request->all();
    $data['user_id'] = $this->user->id;

    $validator = Validator::make($data, [
      'name' => 'required|string|max:255',
      'last_name' => 'required|string|max:255',
      'phone_number' => 'required|string|max:50',
      'title' => 'required|string|max:255',
      'description' => 'nullable|string',
      'status' => 'required|in:unemployed,selfemployed,employed,student,retired',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $professional = Professional::create($data);

    return response()->json($professional, 201);
  }
}

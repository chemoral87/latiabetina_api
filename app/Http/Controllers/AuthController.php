<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
  public function login() {
    $credentials = request(['email', 'password']);

    if (!$token = auth()->attempt($credentials)) {

      return response()->json(['errors' => [
        'password' => trans('auth.failed'),
      ]], 401);

    }

    // Update last login time
    $user = auth()->user();
    if ($user) {
      $user->update(['last_login_at' => now()]);
    }

    return $this->respondWithToken($token);
  }

  public function me() {
    $user = auth()->user();

    if (!$user) {
      return response()->json(['error' => 'Unauthorized'], 401);
    }

    return response()->json(
      new UserResource($user)
    );
  }

  public function logout() {
    try {
      auth()->logout();
    } catch (\Exception $e) {
      // Token might be invalid or expired, but logout still succeeds
    }

    return response()->json(['message' => 'Successfully logged out']);
  }

  public function refresh() {
    // refresh token
    return $this->respondWithToken(auth()->refresh());
  }

  protected function respondWithToken($token) {
    return response()->json([
      'access_token' => $token,
      'token_type' => 'bearer',
      //'expires_in' => auth()->factory()->getTTL() * 60,
      'expires_in' => auth('api')->factory()->getTTL() * 60,
    ]);
  }

}

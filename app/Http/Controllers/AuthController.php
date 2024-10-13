<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// // use Tymon\JWTAuth\JWTAuth;

class AuthController extends Controller {
  public function login() {
    // https://medium.com/mesan-digital/tutorial-5-how-to-build-a-laravel-5-4-jwt-authentication-api-with-e-mail-verification-61d3f356f823
    $credentials = request(['email', 'password']);

    if (!$token = auth()->attempt($credentials)) {

      return response()->json(['errors' => [
        'password' => trans('auth.failed'),
      ]], 401);

    }

    return $this->respondWithToken($token);
  }

  public function me() {

    return response()->json(
      new UserResource(auth()->user())
    );
  }

  public function logout() {
    auth()->logout();

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

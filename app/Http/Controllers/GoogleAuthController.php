<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller {

  /**
   * Redirect the user to the Google authentication page.
   */
  public function redirectToGoogle() {
    return Socialite::driver('google')->stateless()->redirect();
  }

  /**
   * Obtain the user information from Google.
   */
  public function handleGoogleCallback(Request $request) {
    try {
      // Obtener el usuario de Google
      $googleUser = Socialite::driver('google')->stateless()->user();

      $user = $this->findOrCreateUser($googleUser);

      // Generar token JWT
      $token = Auth::guard('api')->login($user);

      // Redirigir al frontend con el token
      $frontendUrl = config('app.frontend_url');
      return redirect()->away("{$frontendUrl}/auth/google/callback?token={$token}");

    } catch (\Exception $e) {
      $frontendUrl = config('app.frontend_url');
      return redirect()->away("{$frontendUrl}/auth/google/error?message=" . urlencode($e->getMessage()));
    }
  }

  /**
   * Handle Google authentication with JWT token (for Nuxt Auth)
   */
  public function handleGoogleToken(Request $request) {
    $token = $request->input('token');

    if (!$token) {
      return response()->json(['error' => 'Token is required'], 400);
    }

    try {
      $user = auth()->setToken($token)->user();

      if (!$user) {
        return response()->json(['error' => 'Invalid token'], 401);
      }

      return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        'user' => [
          'id' => $user->id,
          'name' => $user->name,
          'last_name' => $user->last_name,
          'email' => $user->email,
          'avatar' => $user->avatar,
          'google_id' => $user->google_id,
        ],
      ]);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
    }
  }

  /**
   * Validate JWT token from callback and return user info
   * (Para usar con Nuxt Auth cuando el token ya fue generado por el callback)
   */
  public function validateToken(Request $request) {
    $request->validate([
      'token' => 'required|string',
    ]);

    try {
      // Validar el JWT token
      $user = Auth::guard('api')->setToken($request->token)->user();

      if (!$user) {
        return response()->json([
          'status' => 'error',
          'message' => 'Token inválido',
        ], 401);
      }

      return response()->json([
        'access_token' => $request->token,
        'token_type' => 'bearer',
        'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        'user' => [
          'id' => $user->id,
          'name' => $user->name,
          'last_name' => $user->last_name,
          'email' => $user->email,
          'avatar' => $user->avatar,
          'google_id' => $user->google_id,
          'permissions_org' => $user->permissions_org ?? [],
          'orgs' => $user->orgs ?? [],
        ],
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'Token inválido: ' . $e->getMessage(),
      ], 401);
    }
  }

  /**
   * Encuentra o crea un usuario basado en los datos de Google
   */
  private function findOrCreateUser($googleUser) {
    // Buscar usuario por google_id primero (más rápido con índice)
    $user = User::where('google_id', $googleUser->id)->first();

    if ($user) {
      // Solo actualizar avatar si realmente cambió (evita escritura innecesaria)
      if ($user->avatar !== $googleUser->avatar) {
        $user->avatar = $googleUser->avatar;
        $user->save();
      }
      return $user;
    }

    // Buscar por email solo si no existe por google_id
    $user = User::where('email', $googleUser->email)->first();

    if ($user) {
      // Vincular cuenta existente con Google (una sola escritura)
      $user->google_id = $googleUser->id;
      $user->avatar = $googleUser->avatar;
      $user->save();
      return $user;
    }

    // Crear nuevo usuario (optimizado)
    $names = explode(' ', $googleUser->name, 2);
    return User::create([
      'name' => $names[0] ?? $googleUser->name,
      'last_name' => $names[1] ?? '',
      'email' => $googleUser->email,
      'google_id' => $googleUser->id,
      'avatar' => $googleUser->avatar,
      'password' => bcrypt(Str::random(16)), // Reducido de 32 a 16 caracteres
      'email_verified_at' => now(),
    ]);
  }

  /**
   * Logout de Google (invalida JWT y limpia sesión)
   */
  public function logout(Request $request) {
    try {
      auth()->logout();

      return response()->json([
        'status' => 'success',
        'message' => 'Sesión cerrada exitosamente',
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'Error al cerrar sesión',
      ], 500);
    }
  }
}

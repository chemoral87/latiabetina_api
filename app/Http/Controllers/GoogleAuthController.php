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
  public function redirectToGoogle(Request $request) {
    // Get the frontend URL from query parameter or referer header
    $frontendUrl = $request->query('frontend_url');

    if (!$frontendUrl) {
      $referer = $request->header('referer');
      if ($referer) {
        $parsedUrl = parse_url($referer);
        if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
          $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
          $frontendUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $port;
        }
      }
    }

    // If we still don't have a frontend URL, use the default
    if (!$frontendUrl) {
      $frontendUrl = config('app.frontend_url');
    }

    // Store in state parameter for OAuth callback
    $state = base64_encode(json_encode(['frontend_url' => $frontendUrl]));

    return Socialite::driver('google')->with(['state' => $state])->stateless()->redirect();
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

      // Determinar la URL del frontend basándose en el state parameter
      $frontendUrl = config('app.frontend_url'); // default

      $state = $request->query('state');
      if ($state) {
        $decoded = json_decode(base64_decode($state), true);
        if (isset($decoded['frontend_url']) && $decoded['frontend_url']) {
          $frontendUrl = $decoded['frontend_url'];
        }
      }

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

      // Build permissions_org and orgs as in UserResource
      $permissions_orgs = [];
      $orgs = [];
      foreach ($user->profiles as $profile) {
        $orgCode = $profile->organization->short_code;
        $orgs[] = [
          'id' => $profile->org_id,
          'name' => $profile->organization->name,
          'short_code' => $orgCode,
        ];
        foreach ($profile->roles as $role) {
          foreach ($role->permissions as $permission) {
            $permissions_orgs[$permission->name][$profile->org_id] = true;
          }
        }
        foreach ($profile->permissions as $permission) {
          $permissions_orgs[$permission->name][$profile->org_id] = true;
        }
      }
      foreach ($permissions_orgs as &$orgIds) {
        $orgIds = array_keys($orgIds);
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
          'permissions_org' => $permissions_orgs,
          'orgs' => $orgs,
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

      // Build permissions_org and orgs as in UserResource
      $permissions_orgs = [];
      $orgs = [];
      foreach ($user->profiles as $profile) {
        $orgCode = $profile->organization->short_code;
        $orgs[] = [
          'id' => $profile->org_id,
          'name' => $profile->organization->name,
          'short_code' => $orgCode,
        ];
        foreach ($profile->roles as $role) {
          foreach ($role->permissions as $permission) {
            $permissions_orgs[$permission->name][$profile->org_id] = true;
          }
        }
        foreach ($profile->permissions as $permission) {
          $permissions_orgs[$permission->name][$profile->org_id] = true;
        }
      }
      foreach ($permissions_orgs as &$orgIds) {
        $orgIds = array_keys($orgIds);
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
          'permissions_org' => $permissions_orgs,
          'orgs' => $orgs,
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
      // Actualizar información del usuario con datos de Google
      $names = explode(' ', $googleUser->name, 2);
      $updated = false;

      if ($user->name !== ($names[0] ?? $googleUser->name)) {
        $user->name = $names[0] ?? $googleUser->name;
        $updated = true;
      }

      if ($user->last_name !== ($names[1] ?? '')) {
        $user->last_name = $names[1] ?? '';
        $updated = true;
      }

      if ($user->avatar !== $googleUser->avatar) {
        $user->avatar = $googleUser->avatar;
        $updated = true;
      }

      if ($updated) {
        $user->save();
      }

      return $user;
    }

    // Buscar por email solo si no existe por google_id
    $user = User::where('email', $googleUser->email)->first();

    if ($user) {
      // Vincular cuenta existente con Google y actualizar información
      $names = explode(' ', $googleUser->name, 2);
      $user->google_id = $googleUser->id;
      $user->name = $names[0] ?? $googleUser->name;
      $user->last_name = $names[1] ?? '';
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




<?php

use App\Http\Controllers\AuditoriumController;
use App\Http\Controllers\AuditoriumEventController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */
Route::get("test", function () {
  return "ok test - " . date("d  Y h:i:s A");
});

Route::group(['middleware' => ['api']], function () {
  Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('user', 'me');
  });

  Route::prefix('user')->controller(UserController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('send-code', 'sendResetCode')->middleware('throttle:3,1');
    Route::post('reset-password', 'resetPassword');
  });
});

// Rutas de Google OAuth con middleware web para callbacks
Route::middleware('web')->prefix('auth/google')->controller(GoogleAuthController::class)->group(function () {
  Route::get('redirect', 'redirectToGoogle');
  Route::get('callback', 'handleGoogleCallback');
});

// Ruta para mobile/SPA sin middleware de sesión
Route::post('auth/google/token', [GoogleAuthController::class, 'handleGoogleToken']);
Route::post('auth/google/validate', [GoogleAuthController::class, 'validateToken']);

Route::group(['middleware' => ['jwt.verify']], function () {
  Route::prefix('user')->controller(UserController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::put('/{id}/children', 'children');
    Route::delete('/{id}', 'delete');
    Route::post('/change', 'changePassword');
  });

  Route::prefix('role')->controller(RoleController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
    Route::put('/{id}/children', 'children');
  });

  Route::prefix('auditorium')->controller(AuditoriumController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
  });

  Route::prefix('auditorium-event')->controller(AuditoriumEventController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
  });

  Route::prefix('permission')->controller(PermissionController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
  });

  Route::prefix('organization')->controller(OrganizationController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
  });

  Route::prefix('profile')->controller(ProfileController::class)->group(function () {
    Route::get("/{user_id}", "index");
    // Route::get("/filter", "{$controller}@filter");
    Route::get("/{user_id}/{id}", "show");
    Route::post("/{user_id}", "create");
    Route::post("/{user_id}/{id}/favorite", "favorite");
    Route::put("/{user_id}/{id}", "update");
    Route::delete("/{user_id}/{id}", "delete");
  });

  Route::prefix('store')->controller(StoreController::class)->group(function () {
    Route::get("/", 'index');
    Route::get("/{id}", 'show');
    Route::post("/", 'create');
    Route::put("/{id}", 'update');
    Route::delete("/{id}", 'delete');
  });

});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//   return $request->user();
// });

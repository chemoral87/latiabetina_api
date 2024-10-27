<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
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

Route::group(["prefix" => "auth", "middleware" => ["api"]], function ($router) {
  Route::post("login", [AuthController::class, 'login']);
  Route::post("logout", [AuthController::class, 'logout']);
  Route::post("refresh", [AuthController::class, 'refresh']);
  Route::post("user", [AuthController::class, 'me']);
});

// Route::group(["prefix" => "auth", "middleware" => ["api"]], function ($router) {
//   $controller = "AuthController";
//   Route::post("login", "{$controller}@login");
//   Route::post("logout", "{$controller}@logout");
//   Route::post("refresh", "{$controller}@refresh");
//   Route::post("user", "{$controller}@me");

// });

Route::group(["prefix" => "users", "middleware" => ["api"]], function ($router) {
  Route::post("register", [UserController::class, 'register']);
  Route::post("send-code", [UserController::class, 'sendResetCode']);
  Route::post("reset-password", [UserController::class, 'resetPassword']);
});

Route::group(['middleware' => ['jwt.verify']], function () {
  Route::prefix('users')->controller(UserController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::put('/{id}/children', 'children');
    Route::delete('/{id}', 'delete');
    Route::post('/change', 'changePassword');
  });

  Route::prefix('roles')->controller(RoleController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
    Route::put('/{id}/children', 'children');
  });

  Route::prefix('permissions')->controller(PermissionController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
  });

  Route::prefix('organizations')->controller(OrganizationController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
  });

  Route::prefix('profiles')->controller(ProfileController::class)->group(function () {
    Route::get("/{user_id}", "index");
    // Route::get("/filter", "{$controller}@filter");
    Route::get("/{user_id}/{id}", "show");
    Route::post("/{user_id}", "create");
    Route::post("/{user_id}/{id}/favorite", "favorite");
    Route::put("/{user_id}/{id}", "update");
    Route::delete("/{user_id}/{id}", "delete");
  });
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//   return $request->user();
// });

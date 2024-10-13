<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
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

Route::group(["prefix" => "users"], function () {
  $controller = "UserController";
  Route::post("/register", "{$controller}@register");
  Route::post("/send-code", "{$controller}@sendResetCode");
  Route::post("/reset-password", "{$controller}@resetPassword");
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
  return $request->user();
});

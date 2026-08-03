<?php

use App\Http\Controllers\AuditoriumController;
use App\Http\Controllers\AuditoriumEventController;
use App\Http\Controllers\AuditoriumEventSeatController;
use App\Http\Controllers\AuditoriumEventSeatLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChurchEventController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LifeGroupController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TestimonyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChurchMemberController;
use App\Http\Controllers\ConsoSheetController;
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

// Public endpoint for creating testimonies (no API middleware
Route::group(['middleware' => ['api']], function () {
  Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('user', 'me');
  });


  // ========================
  // Life Groups - Reports
  // ========================
  Route::prefix('life-groups/reports')->controller(\App\Http\Controllers\LifeGroupReportController::class)->group(function () {
    Route::get('/attendance-by-session', 'attendanceBySession');
    Route::get('/attendance-by-group', 'attendanceByGroup');
    Route::get('/new-guests', 'newGuests');
    Route::get('/recurrent-people', 'recurrentPeople');
  });

  Route::prefix('testimony')->controller(TestimonyController::class)->group(function () {
    Route::get('/public', 'publicIndex');
    Route::post('/', 'store');
  });

  Route::prefix('church-event')->controller(ChurchEventController::class)->group(function () {
    Route::get('/public', 'publicIndex');
    Route::get('/public/carousel', 'publicCarousel');
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
    Route::get('/', 'index')->middleware('permission_org:user-index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create')->middleware('permission_org:user-create');
    Route::put('/{id}', 'update')->middleware('permission_org:user-update');
    Route::put('/{id}/children', 'children');
    Route::delete('/{id}', 'delete')->middleware('permission_org:user-delete');
    Route::post('/change', 'changePassword');
  });

  Route::prefix('role')->controller(RoleController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:role-index');
    Route::get('/filter', 'filter');
    Route::get('/{id}/distribution', 'distribution');
    Route::get('/{id}', 'show');
    Route::post('/', 'create')->middleware('permission_org:role-create');
    Route::put('/{id}', 'update')->middleware('permission_org:role-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:role-delete');
    Route::put('/{id}/children', 'children');
    Route::post('/{id}/permission', 'addPermission');
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

  Route::prefix('auditorium-event-seat')->controller(AuditoriumEventSeatController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
  });

  Route::prefix('auditorium-event-seat-log')->controller(AuditoriumEventSeatLogController::class)->group(function () {
    Route::get('/', 'index');
  });

  Route::prefix('church-event')->controller(ChurchEventController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/calendar', 'calendar');
    Route::get('/{churchEvent}', 'show');
    Route::post('/', 'store');
    Route::post('/{churchEvent}/copy', 'copy');
    Route::put('/{churchEvent}', 'update');
    Route::delete('/{churchEvent}', 'destroy');
  });

  Route::prefix('permission')->controller(PermissionController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:permission-index');
    Route::get('/filter', 'filter');
    Route::get('/{id}/distribution', 'distribution');
    Route::post('/', 'create')->middleware('permission_org:permission-create');
    Route::put('/{id}', 'update')->middleware('permission_org:permission-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:permission-delete');
  });

  Route::prefix('product')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/pos', 'pos');
    Route::post('/reorder', 'reorder');
    Route::get('/{product}', 'show');
    Route::post('/', 'store');
    Route::put('/{product}', 'update');
    Route::delete('/{product}', 'destroy');
  });

  Route::prefix('sale')->controller(SaleController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/daily', 'daily');
    Route::get('/kds', 'kds');
    Route::get('/{sale}', 'show');
    Route::post('/', 'store');
    Route::patch('/{sale}/complete', 'complete');
    Route::patch('/{sale}/item/{saleItem}', 'updateItem');
    Route::put('/{sale}', 'update');
    Route::delete('/{sale}', 'destroy');
  });

  Route::prefix('organization')->controller(OrganizationController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:organization-index');
    Route::get('/filter', 'filter');
    Route::get('/{id}', 'show');
    Route::post('/', 'create')->middleware('permission_org:organization-create');
    Route::put('/{id}', 'update')->middleware('permission_org:organization-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:organization-delete');
  });

  Route::prefix('profile')->controller(ProfileController::class)->group(function () {
    Route::get("/{user_id}", "index");
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

  Route::prefix('conso-sheet')->controller(ConsoSheetController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
  });

  Route::prefix('church-member')->controller(ChurchMemberController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'create');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'delete');
  });


  // ========================
  // Life Groups - Reports
  // ========================
  Route::prefix('life-groups/reports')->controller(\App\Http\Controllers\LifeGroupReportController::class)->group(function () {
    Route::get('/attendance-by-session', 'attendanceBySession');
    Route::get('/attendance-by-group', 'attendanceByGroup');
    Route::get('/new-guests', 'newGuests');
    Route::get('/recurrent-people', 'recurrentPeople');
  });

  Route::prefix('testimony')->controller(TestimonyController::class)->group(function () {
    Route::get('/', 'index');
    Route::get("/{id}", 'show');
    Route::put('/{id}/status', 'updateStatus');
    Route::put("/{id}", "update");
  });

  Route::prefix('whatsapp')->controller(\App\Http\Controllers\WhatsAppController::class)->group(function () {
    Route::get('/status', 'status');
    Route::post('/send', 'sendMessage');
    Route::get('/logs', 'logs');
  });

  // ========================
  // Life Groups (Redes de Vida)
  // ========================
  Route::prefix('life-groups')->controller(LifeGroupController::class)->group(function () {
    // Specific routes FIRST (before wildcard {id})
    Route::get('/dashboard', 'dashboard');
    Route::get('/people/search', 'searchPeople');
    Route::post('/people', 'storePerson');
    Route::get('/sessions/{sessionId}/attendance', 'getAttendance');
    Route::post('/sessions/{sessionId}/attendance', 'registerAttendance');
    Route::put('/sessions/{id}', 'updateSession');

    // Wildcard routes LAST
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
  });
});

// Test WebSocket Route
Route::get('/test-websocket', function () {
  $message = request('message', 'Hola desde WebSockets!');
  $data = request('data', ['timestamp' => now()]);

  event(new \App\Events\TestWebSocketEvent($message, $data));

  return response()->json([
    'status' => 'success',
    'message' => 'Evento enviado',
    'data' => [
      'message' => $message,
      'data' => $data,
    ],
  ]);
});

Route::get('/test-google-user', function () {
  $user = \App\Models\User::orderBy('id', 'desc')->first();
  $token = \Illuminate\Support\Facades\Auth::guard('api')->login($user);

  $permissions_orgs = [];
  $orgs = [];
  try {
      foreach ($user->profiles as $profile) {
        $orgCode = $profile->organization ? $profile->organization->short_code : null;
      }
  } catch (\Exception $e) {
      return ['error' => 'profiles error: ' . $e->getMessage()];
  }

  return [
    'user_id' => $user->id,
    'token' => $token,
    'resource' => new \App\Http\Resources\UserResource($user)
  ];
});

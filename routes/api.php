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
use App\Http\Controllers\SongController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TestimonyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChurchMemberController;
use App\Http\Controllers\ConsoSheetController;
use App\Http\Controllers\ExpenseCategoriesController;
use App\Http\Controllers\ExpenseConceptsController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ExpenseTicketsController;
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
    Route::get('/filter', 'filter')->middleware('permission_org:user-filter');
    Route::get('/{id}', 'show')->middleware('permission_org:user-index');
    Route::post('/', 'create')->middleware('permission_org:user-create');
    Route::put('/{id}', 'update')->middleware('permission_org:user-update');
    Route::put('/{id}/children', 'children')->middleware('permission_org:user-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:user-delete');
    Route::post('/change', 'changePassword');
  });

  Route::prefix('role')->controller(RoleController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:role-index');
    Route::get('/filter', 'filter')->middleware('permission_org:role-filter');
    Route::get('/{id}/distribution', 'distribution')->middleware('permission_org:role-index');
    Route::get('/{id}', 'show')->middleware('permission_org:role-index');
    Route::post('/', 'create')->middleware('permission_org:role-create');
    Route::put('/{id}', 'update')->middleware('permission_org:role-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:role-delete');
    Route::put('/{id}/children', 'children')->middleware('permission_org:role-update');
    Route::post('/{id}/permission', 'addPermission')->middleware('permission_org:role-update');
  });

  Route::prefix('auditorium')->controller(AuditoriumController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:auditorium-index');
    Route::get('/filter', 'filter')->middleware('permission_org:auditorium-filter');
    Route::get('/{id}', 'show')->middleware('permission_org:auditorium-index');
    Route::post('/', 'create')->middleware('permission_org:auditorium-create');
    Route::put('/{id}', 'update')->middleware('permission_org:auditorium-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:auditorium-delete');
  });

  Route::prefix('auditorium-event')->controller(AuditoriumEventController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:auditorium-event-index');
    Route::get('/{id}', 'show')->middleware('permission_org:auditorium-event-index,auditorium-event-mark');
    Route::post('/', 'store')->middleware('permission_org:auditorium-event-create');
    Route::put('/{id}', 'update')->middleware('permission_org:auditorium-event-update');
    Route::delete('/{id}', 'destroy')->middleware('permission_org:auditorium-event-delete');
  });

  Route::prefix('auditorium-event-seat')->controller(AuditoriumEventSeatController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:auditorium-event-mark');
    Route::post('/', 'store')->middleware('permission_org:auditorium-event-mark');
  });

  Route::prefix('auditorium-event-seat-log')->controller(AuditoriumEventSeatLogController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:auditorium-event-mark');
  });

  Route::prefix('church-event')->controller(ChurchEventController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:church-event-index');
    Route::get('/calendar', 'calendar')->middleware('permission_org:church-event-index');
    Route::get('/{churchEvent}', 'show')->middleware('permission_org:church-event-index');
    Route::post('/', 'store')->middleware('permission_org:church-event-create');
    Route::post('/{churchEvent}/copy', 'copy')->middleware('permission_org:church-event-create');
    Route::put('/{churchEvent}', 'update')->middleware('permission_org:church-event-update');
    Route::delete('/{churchEvent}', 'destroy')->middleware('permission_org:church-event-delete');
  });

  Route::prefix('permission')->controller(PermissionController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:permission-index');
    Route::get('/filter', 'filter')->middleware('permission_org:permission-filter');
    Route::get('/{id}/distribution', 'distribution')->middleware('permission_org:permission-index');
    Route::post('/', 'create')->middleware('permission_org:permission-create');
    Route::put('/{id}', 'update')->middleware('permission_org:permission-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:permission-delete');
  });

  Route::prefix('product')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:product-index');
    Route::get('/pos', 'pos')->middleware('permission_org:product-index');
    Route::post('/reorder', 'reorder')->middleware('permission_org:product-update');
    Route::get('/{product}', 'show')->middleware('permission_org:product-index');
    Route::post('/', 'store')->middleware('permission_org:product-create');
    Route::put('/{product}', 'update')->middleware('permission_org:product-update');
    Route::delete('/{product}', 'destroy')->middleware('permission_org:product-delete');
  });

  Route::prefix('sale')->controller(SaleController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:sale-index');
    Route::get('/daily', 'daily')->middleware('permission_org:sale-index');
    Route::get('/kds', 'kds')->middleware('permission_org:pos-kds');
    Route::get('/{sale}', 'show')->middleware('permission_org:sale-index');
    Route::post('/', 'store')->middleware('permission_org:sale-create');
    Route::patch('/{sale}/complete', 'complete')->middleware('permission_org:sale-update');
    Route::patch('/{sale}/item/{saleItem}', 'updateItem')->middleware('permission_org:sale-update');
    Route::put('/{sale}', 'update')->middleware('permission_org:sale-update');
    Route::delete('/{sale}', 'destroy')->middleware('permission_org:sale-delete');
  });

  Route::prefix('organization')->controller(OrganizationController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:organization-index');
    Route::get('/filter', 'filter')->middleware('permission_org:organization-filter');
    Route::get('/{id}', 'show')->middleware('permission_org:organization-index');
    Route::post('/', 'create')->middleware('permission_org:organization-create');
    Route::put('/{id}', 'update')->middleware('permission_org:organization-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:organization-delete');
  });

  Route::prefix('profile')->controller(ProfileController::class)->group(function () {
    Route::get("/{user_id}", "index")->middleware('permission_org:profile-index');
    Route::get("/{user_id}/{id}", "show")->middleware('permission_org:profile-index');
    Route::post("/{user_id}", "create")->middleware('permission_org:profile-create');
    Route::post("/{user_id}/{id}/favorite", "favorite");
    Route::put("/{user_id}/{id}", "update")->middleware('permission_org:profile-update');
    Route::delete("/{user_id}/{id}", "delete")->middleware('permission_org:profile-delete');
  });

  Route::prefix('store')->controller(StoreController::class)->group(function () {
    Route::get("/", 'index')->middleware('permission_org:store-index');
    Route::get("/{id}", 'show')->middleware('permission_org:store-index');
    Route::post("/", 'create')->middleware('permission_org:store-create');
    Route::put("/{id}", 'update')->middleware('permission_org:store-update');
    Route::delete("/{id}", 'delete')->middleware('permission_org:store-delete');
  });

  Route::prefix('expense-tickets')->controller(ExpenseTicketsController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:expense-ticket-index');
    Route::get('/{id}', 'show')->middleware('permission_org:expense-ticket-index');
    Route::post('/', 'create')->middleware('permission_org:expense-ticket-create');
    Route::put('/{id}', 'update')->middleware('permission_org:expense-ticket-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:expense-ticket-delete');
  });

  Route::prefix('expenses')->controller(ExpensesController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:expense-ticket-index');
    Route::get('/{id}', 'show')->middleware('permission_org:expense-ticket-index');
    Route::post('/', 'create')->middleware('permission_org:expense-ticket-create');
    Route::put('/{id}', 'update')->middleware('permission_org:expense-ticket-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:expense-ticket-delete');
  });

  Route::prefix('expense-categories')->controller(ExpenseCategoriesController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:expense-ticket-index');
    Route::get('/{id}', 'show')->middleware('permission_org:expense-ticket-index');
    Route::post('/', 'create')->middleware('permission_org:expense-ticket-create');
    Route::put('/{id}', 'update')->middleware('permission_org:expense-ticket-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:expense-ticket-delete');
  });

  Route::prefix('expense-concepts')->controller(ExpenseConceptsController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:expense-ticket-index');
    Route::get('/{id}', 'show')->middleware('permission_org:expense-ticket-index');
    Route::post('/', 'create')->middleware('permission_org:expense-ticket-create');
    Route::put('/{id}', 'update')->middleware('permission_org:expense-ticket-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:expense-ticket-delete');
  });

  Route::prefix('conso-sheet')->controller(ConsoSheetController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:conso-sheet-index');
    Route::get('/consolidators', 'consolidators')->middleware('permission_org:conso-sheet-index');
    Route::get('/{id}', 'show')->middleware('permission_org:conso-sheet-index');
    Route::post('/', 'create')->middleware('permission_org:conso-sheet-create');
    Route::put('/{id}', 'update')->middleware('permission_org:conso-sheet-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:conso-sheet-delete');
  });

  Route::prefix('church-member')->controller(ChurchMemberController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:church-member-index');
    Route::get('/{id}', 'show')->middleware('permission_org:church-member-index');
    Route::post('/', 'create')->middleware('permission_org:church-member-create');
    Route::put('/{id}', 'update')->middleware('permission_org:church-member-update');
    Route::delete('/{id}', 'delete')->middleware('permission_org:church-member-delete');
    Route::get('/{id}/tracking-logs', 'trackingLogs')->middleware('permission_org:church-member-index');
    Route::post('/{id}/tracking-logs', 'storeTrackingLog')->middleware('permission_org:conso-sheet-index');
    Route::put('/{id}/tracking-logs/{logId}', 'updateTrackingLog')->middleware('permission_org:conso-sheet-index');
    Route::delete('/{id}/tracking-logs/{logId}', 'deleteTrackingLog')->middleware('permission_org:conso-sheet-index');
    Route::put('/{id}/status', 'updateStatus')->middleware('permission_org:conso-sheet-index');
    Route::get('/{id}/status-logs', 'statusLogs')->middleware('permission_org:conso-sheet-index');
    Route::get('/{id}/consolidators', 'consolidators')->middleware('permission_org:church-member-consolidator-assign');
    Route::put('/{id}/consolidators', 'syncConsolidators')->middleware('permission_org:church-member-consolidator-assign');
    Route::get('/{id}/medals', 'medals')->middleware('permission_org:conso-sheet-index');
    Route::post('/{id}/medals', 'storeMedal')->middleware('permission_org:conso-sheet-index');
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
    Route::get('/', 'index')->middleware('permission_org:testimony-index');
    Route::get("/{id}", 'show')->middleware('permission_org:testimony-index');
    Route::put('/{id}/status', 'updateStatus')->middleware('permission_org:testimony-update');
    Route::put("/{id}", "update")->middleware('permission_org:testimony-update');
  });

  Route::prefix('song')->controller(SongController::class)->group(function () {
    Route::get('/', 'index')->middleware('permission_org:song-index');
    Route::get('/{song}', 'show')->middleware('permission_org:song-index');
    Route::post('/', 'store')->middleware('permission_org:song-create');
    Route::put('/{song}', 'update')->middleware('permission_org:song-update');
    Route::delete('/{song}', 'destroy')->middleware('permission_org:song-delete');
  });

  Route::prefix('whatsapp')->controller(\App\Http\Controllers\WhatsAppController::class)->group(function () {
    Route::get('/status', 'status')->middleware('permission_org:whatsapp-index');
    Route::post('/send', 'sendMessage')->middleware('permission_org:whatsapp-send');
    Route::get('/logs', 'logs')->middleware('permission_org:whatsapp-index');
    Route::post('/logs/{id}/resend', 'resend')->middleware('permission_org:whatsapp-send');
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

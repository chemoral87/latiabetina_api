# Latiabetina API - Backend Development Guidelines

## General

- **Framework**: Laravel 10.x, PHP ^8.2
- **Database**: MySQL (utf8mb4_unicode_ci, strict mode)
- **Cache/Queue**: Redis (phpredis client)
- **Auth**: JWT (tymon/jwt-auth, HS256, TTL=60min, refresh=20160min)
- **Permissions**: Spatie laravel-permission v5 (no teams mode, team_id repurposed as org_id)
- **Auditing**: owen-it/laravel-auditing v13 (database driver)
- **Broadcasting**: Laravel Reverb (Pusher-compatible)
- **File Storage**: AWS S3 (public URLs via `Storage::disk('s3')->url()`)
- **Image Processing**: Intervention Image v3 (GD driver, WebP output)
- **Locale**: Spanish (`es`) via laraveles/spanish package

---

## Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | `PascalCase` | `ChurchEventController`, `ImageTreatmentService` |
| Methods/Functions | `camelCase` | `applyOrgPermissionScope()`, `getOrgsByPermission()` |
| Database tables | `snake_case` plural | `pos_products`, `sale_event_logs`, `life_group_people` |
| Database columns | `snake_case` | `org_id`, `url_image`, `created_by`, `slug_name` |
| JSON response keys | `snake_case` | `access_token`, `token_type`, `items_per_page` |
| Route prefixes | `kebab-case` | `church-event`, `life-groups`, `conso-sheet` |
| Permission names | `kebab-case` resource.action | `church-event-index`, `product-create` |
| Route file patterns | `{resource}.{action}` | `church-event.index`, `product.store` |
| Relationships | `camelCase` | `organization()`, `creator()`, `churchMembers()` |
| Accessors | `get{Field}Attribute` | `getUrlImageS3Attribute()` |

---

## Code Style

- **Indentation**: 2 spaces
- **Braces**: Opening brace on same line as class/method declaration
- **Imports**: Grouped: framework classes first, then app classes, then external packages
- **PHP opening tag**: `<?php` only, no trailing whitespace
- **Type hints**: Use for method parameters and return types where possible
- **Strict types**: Not used (no `declare(strict_types=1)`)

---

## Directory Structure (app/)

```
app/
├── Console/Kernel.php
├── Events/           -> ShouldBroadcast events
├── Exceptions/Handler.php
├── helpers.php       -> Global helper functions (auto-loaded via composer.json)
├── Http/
│   ├── Controllers/
│   │   ├── Concerns/ -> Reusable traits (e.g., AppliesOrgPermissionScope)
│   │   ├── Controller.php (base)
│   │   ├── AuthController.php
│   │   ├── *Controller.php (resource controllers)
│   │   └── ...
│   ├── Kernel.php
│   ├── Middleware/    -> JwtMiddleware, ConvertRequestKeysToSnakeCase, etc.
│   └── Resources/    -> DataSetResource, UserResource, etc.
├── Jobs/             -> Queueable jobs (e.g., SendWhatsAppMessageJob)
├── Models/           -> Eloquent models
│   └── LifeGroup/    -> Sub-namespace for domain aggregate
├── Notifications/    -> Mail notifications
├── Providers/        -> Service providers
└── Services/         -> Business logic services
```

---

## Controllers

### Base Controller
- Extend `App\Http\Controllers\Controller` (extends `Illuminate\Routing\Controller`)
- Uses `AuthorizesRequests` and `ValidatesRequests` traits

### Resource Controller Pattern

```php
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;

class ProductController extends Controller {
    use AppliesOrgPermissionScope;

    public function index(Request $request) {
        $query = Product::query();
        $query = $this->applyOrgPermissionScope($query, $request->user(), 'product-index');

        queryServerSide($request, $query);

        if ($request->filter) {
            $query->where('name', 'like', "%{$request->filter}%");
        }

        $perPage = (int) $request->get('itemsPerPage', 10);
        if ($perPage === -1) {
            $perPage = $query->count() ?: 1;
        }

        return new DataSetResource($query->paginate($perPage));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        if ($request->image && str_starts_with($request->image, 'data:')) {
            $blob = treatImage($request->image);
            $data['image'] = saveS3Blob($blob, "PROD/{$orgId}/products/" . now()->format('Ymd') . '/' . Str::uuid() . '.webp');
        }

        $data['org_id'] = $request->user()->org_id;
        $data['created_by'] = $request->user()->id;

        $model = Product::create($data);

        return response()->json(['success' => __('messa.product_create'), 'data' => $model], 201);
    }

    public function show(Product $product) {
        return $product->makeVisible('image')->append('image_s3');
    }

    public function update(Request $request, Product $product) {
        // Similar to store, but with unique:table,column,$product->id
    }

    public function destroy(Product $product) {
        $product->delete();
        return response()->json(['success' => __('messa.product_delete')]);
    }
}
```

### Key Patterns
- **Index**: `DataSetResource($query->paginate(...))` with `queryServerSide()` for sorting
- **Store**: `$request->validate([...])` inline, return 201 with `__('messa.{resource}_{action}')`
- **Show**: Route-model binding, `makeVisible()->append()` for S3 URLs
- **Update**: Return 200
- **Destroy**: Soft delete, return 200
- **Org-scoping**: Use `$this->applyOrgPermissionScope($query, $user, 'permission-name')` trait
- **Custom methods**: Named descriptively (`calendar()`, `pos()`, `reorder()`, `publicIndex()`)
- **No Form Request classes**: Use inline `$request->validate([...])`

---

## Models

### Base Structure

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model {
    use HasFactory, SoftDeletes;

    protected $table = 'pos_products';  // Explicit when non-standard

    protected $fillable = [
        'name', 'price', 'org_id', 'created_by',
    ];

    protected $hidden = [
        'image',  // S3 internal path
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'hidden' => 'boolean',
        'requires_preparation' => 'boolean',
        'sold_at' => 'datetime',
        'publish_date' => 'date:Y-m-d',
        'categories' => 'json',
    ];

    protected $appends = [
        'image_s3',
    ];

    public function organization() {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getImageS3Attribute() {
        return $this->image ? permanentUrlS3($this->image) : null;
    }
}
```

### Conventions
- **Fillable**: Always whitelist; never use `$guarded`
- **Hidden**: S3 internal paths, passwords, tokens
- **Appends**: Computed S3 URLs via accessors using `permanentUrlS3()` helper
- **Soft Deletes**: Import `SoftDeletes` trait + `$table->softDeletes()` in migration
- **Timestamps**: Always use `$table->timestamps()`
- **Foreign keys**: `org_id`, `created_by`, `updated_by`, `{model}_id`
- **Relations**: Always explicit with foreign key parameter

### User Model Specifics
```php
class User extends Authenticatable implements JWTSubject, AuditableContract {
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Auditable;

    public function getJWTIdentifier() { return $this->getKey(); }
    public function getJWTCustomClaims() { return []; }
    public function getAuditIgnore() { return ['password']; }

    public function profiles() { return $this->hasMany(Profile::class); }

    public function getOrgsByPermission($permission = null) {
        // Iterates profiles/roles/permissions -> returns array of org_ids per permission
    }
}
```

---

## Routes (api.php)

### Structure
```php
// Public endpoints (no JWT)
Route::group(['middleware' => ['api']], function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('logout', 'logout');
        Route::post('refresh', 'refresh');
        Route::post('user', 'me');
    });
    // Other public routes...
});

// Route ordering: specific routes BEFORE wildcard {id}
Route::prefix('life-groups')->controller(LifeGroupController::class)->group(function () {
    Route::get('/dashboard', 'dashboard');  // Specific first
    Route::get('/', 'index');               // Wildcards last
    Route::get('/{id}', 'show');
});

// Protected endpoints
Route::group(['middleware' => ['jwt.verify']], function () {
    Route::prefix('product')->controller(ProductController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/pos', 'pos');             // Custom before show
        Route::get('/{product}', 'show');      // Route-model binding
        Route::post('/', 'store');
        Route::put('/{product}', 'update');
        Route::delete('/{product}', 'destroy');
    });
});
```

### Conventions
- Route-model binding with implicit model name: `{product}`, `{sale}`, `{churchEvent}`
- Specific routes before wildcard `{id}` routes
- Google OAuth routes use `web` middleware
- Rate limiting: `throttle:3,1` on sensitive endpoints

---

## Middleware

- **JwtMiddleware** (`jwt.verify`): Parses JWT, returns 401 with `{'errors': {'status': '...'}}`
- **ConvertRequestKeysToSnakeCase**: Global middleware; converts input keys to both snake_case AND camelCase
- **Kernel aliases**: `jwt.verify` -> JwtMiddleware, `role`/`permission` -> Spatie middlewares

---

## Resources (Transformers)

### DataSetResource (pagination)
```php
class DataSetResource extends JsonResource {
    public function toArray($request) {
        return [
            'data' => $this->items(),
            'total' => $this->total(),
            'itemsPerPage' => (int) $this->perPage(),
        ];
    }
}
```

### Individual Resources
- Extend `JsonResource`
- `toArray($request)` returns associative array
- Used for complex transformations (UserResource builds permission/org maps)
- Simple responses often return model directly without Resource wrapper

---

## Services

- Plain PHP classes in `App\Services`
- No interface-based resolution pattern
- Instantiation: `app(ServiceClass::class)` or `new ServiceClass()`
- **ImageTreatmentService**: Singleton, processes images (resize 1200x1200, WebP)
- **MessagingService**: Twilio-based messaging
- **ChurchEventCopyService**: Date generation with recurrence rules

---

## Validation

- Inline `$request->validate([...])` in controllers (no Form Request classes)
- Pipe syntax: `'required|string|max:255'`
- Update unique: `'unique:table,column,' . $model->id`
- Common rules: `required`, `sometimes`, `nullable`, `string`, `numeric`, `integer`, `boolean`, `date`, `date_format:H:i`, `min:0`, `max:255`, `exists:table,column`, `unique:table,column`, `in:a,b,c`, `array`
- Custom validation exceptions: `throw ValidationException::withMessages([...])`

---

## Helpers (app/helpers.php, auto-loaded)

| Function | Purpose |
|----------|---------|
| `createVerificationCode($length)` | Generate numeric code |
| `encodeUUID($uuid)` / `decodeUUID($shortened)` | Base64 UUID encoding/decoding |
| `saveS3Blob($blob, $path, $file_to_delete)` | Save to S3, returns path or null |
| `treatImage($blob, $quality, $maxWidth, $maxHeight)` | Process image via ImageTreatmentService |
| `permanentUrlS3($path)` | Get public S3 URL via `Storage::disk('s3')->url()` |
| `deleteS3($path)` | Delete from S3 |
| `queryServerSide($request, $query)` | Apply sortBy/sortDesc to query |
| `replace_tags($string, $tags, $force_lower)` | Replace `{{key}}` placeholders |

---

## File Uploads (S3)

1. Receive base64 image in request
2. Check: `str_starts_with($request->image, 'data:')`
3. Process: `treatImage($request->image)` -> resize to max 1200x1200, convert to WebP
4. Store: `saveS3Blob($blob, "ENV/ORG-{id}/{type}/YYYYMMDD/{uuid}.webp")`
5. Retrieve: `permanentUrlS3($path)` in accessor
6. DB stores only the relative S3 path (hidden from serialization)

---

## Multi-tenancy (Organization-based)

- **Not database-per-tenant**: Org-scoping via `org_id` column on entity tables
- **Profile model**: `user_id`, `org_id`, `favorite` — bridges users to orgs with role/permission assignments
- **Permission scoping**: `AppliesOrgPermissionScope` trait filters queries by org_ids where user has the required permission
- **Spatie team_id**: Repurposed as `org_id` via custom migration

---

## Response Formats

- **Index**: `DataSetResource` — `{ data: [...], total: N, itemsPerPage: N }`
- **Store**: 201 — `{ success: "mensaje", data: { ... } }`
- **Update/Delete**: 200 — `{ success: "mensaje" }`
- **Show**: Direct model JSON (or `{ data: { ... } }` for custom endpoints)
- **Validation Error**: 422 — `{ message: "...", errors: { field: ["..."] } }`
- **Auth Error**: 401 — `{ errors: { status: "..." } }`
- **Not Found**: 404 — `{ message: "Resource not found" }`

---

## Localization

- Spanish translations in `resources/lang/es/`
- Custom messages file: `resources/lang/es/messa.php` (CRUD success messages with `:name` / `:number` placeholders)
- Usage: `__('messa.role_create')`, `trans('auth.failed')`
- Locale set per-notification: `app()->setLocale($notifiable->language ?? 'es')`

---

## Pagination

- `$request->get('itemsPerPage', 10)` — default 10, -1 returns all
- Server-side sort: `queryServerSide($request, $query)` reads `sortBy[]` and `sortDesc[]` arrays
- Default sort: `$query->orderBy('name', 'asc')`
- Response via `DataSetResource`

---

## Migrations

### Naming
```
YYYY_MM_DD_HHMMSS_create_{table_name}_table.php
YYYY_MM_DD_HHMMSS_add_{column}_to_{table}_table.php
```

### Structure
```php
return new class extends Migration {
    public function up(): void {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('table_name');
    }
};
```

---

## Events (Broadcasting)

- Implement `ShouldBroadcast`
- Use `Dispatchable`, `InteractsWithSockets`, `SerializesModels`
- Broadcast to public channel: `Channel('pos.kds.{org_id}')`
- Custom event name via `broadcastAs()`: `sale.created`, `seat.updated`
- Conditional broadcast via `broadcastWhen()`

---

## Jobs

- Queue connection: Redis (or sync for testing)
- Use `RateLimiter::attempt()` for rate limiting (e.g., WhatsApp: 1/sec)
- Manual retry: `$this->release(rand(10, 20))` (seconds)
- Error handling: catch, log to DB, do not re-throw

---

## Testing

- **Unit**: Extend `PHPUnit\Framework\TestCase` (direct instantiation)
- **Feature**: Extend `Tests\TestCase` (Laravel HTTP testing)
- Method names: descriptive snake_case `test_it_generates_dates_for_selected_weekdays_in_a_range`
- Run: `vendor/bin/phpunit` or `php artisan test`

---

## Seeders

- **DatabaseSeeder** calls `$this->call([InitSeeder::class])`
- **InitSeeder**: Idempotent (use `firstOrCreate`), environment-aware
- Permission names: `{resource}-{action}` (`user-index`, `product-create`)
- Role names: `super`, `manager`, `publisher`, `cashier`, `leader`, `worker`, `auditor`

---

## CORS

- Paths: `api/*`, `sanctum/csrf-cookie`
- Methods: `*`
- Origins: localhost, LAN IPs, `*.latiabetina.com`, `*.avivamientomonterrey.com`
- Credentials: true

---

## New Resource Module Checklist (5 rules)

Apply these when wiring any new resource (e.g., the Expense module): routes, permissions, controllers, and docs must ship together.

1. **Gate every route with `permission_org:{resource}-{action}`**
   - All CRUD endpoints live inside the `jwt.verify` group: reads use `-index`, writes use `-create` / `-update` / `-delete`
   - No ungated routes inside the group; keep truly public endpoints (`/public`, registration, OAuth) in the public `api` group
   - Use one permission family per module (e.g., `expense-ticket-*` covers tickets, expenses, categories, concepts)
   - Custom actions map to a sensible permission (`/kds` → `pos-kds`, `/reorder` → `product-update`, `/copy` → `church-event-create`)

2. **Seed every permission referenced by routes**
   - Every `permission_org:` string must exist in `InitSeeder`'s permissions array **and** be granted to the `super` role
   - The seeder must be fully idempotent: `firstOrCreate` for roles and demo users too (not just permissions), so `php artisan db:seed --class=InitSeeder` is safe to re-run on existing DBs

3. **Scope by org even when the anchor is indirect**
   - If the entity links to an org through another table (e.g., ticket → `store_id` → `store.org_id`), scope reads with `getOrgsByPermission(...)` + `whereHas('store')` (nested `whereHas('ticket.store')` for grandchildren); empty org list → `whereRaw('1 = 0')`
   - In `create`/`update`, re-validate the target org and return 403 when it's not allowed (`abort(403, '... not allowed')`)
   - Make the org-anchoring FK required (e.g., `ticket_id` required) so every row is scoped — never create rows that are invisible to the org scope
   - Global reference data with no `org_id` column (categories, concepts) stays unscoped — document that choice

4. **Never wire routes to empty controller stubs**
   - Implement full CRUD when registering routes: `index` (DataSetResource + `queryServerSide()` + filter), `show` (`abort(405)` when missing), `create`/`update` (inline validation matching the migration + `created_by`/`updated_by` from `JWTAuth::user()->id`), `delete`
   - Add the model relations the queries rely on (`store()`, `concept()`, pivot `sync()` / `detach()`) — don't eager-load relations that can't be populated

5. **Keep `ai_permission_mapping/` in sync**
   - One `<permission>.md` per permission: `Files` → `Routes protected` (URI + Controller@method) → `Enforced by` (middleware vs. org scope)
   - Update or regenerate the mapping whenever routes or permissions change (e.g., after adding gates or new endpoints)

# store-delete

Permiso `store-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:store-delete`)

## Routes protected

- `DELETE /store/{id} -> StoreController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\StorePolicy.php` define `delete` con `store-delete` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

# store-create

Permiso `store-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:store-create`)

## Routes protected

- `POST /store -> StoreController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\StorePolicy.php` define `create`/`createForOrg` con `store-create`, pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

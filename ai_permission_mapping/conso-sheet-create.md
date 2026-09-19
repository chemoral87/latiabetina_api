# conso-sheet-create

Permiso `conso-sheet-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-create`)

## Routes protected

- `POST /conso-sheet -> ConsoSheetController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ConsoSheetPolicy.php` define `create`/`createForOrg` con `conso-sheet-create`, pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ConsoSheetController aplica el scope por organización con `conso-sheet-index` (no `conso-sheet-create`).

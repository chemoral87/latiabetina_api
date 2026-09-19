# conso-sheet-update

Permiso `conso-sheet-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-update`)

## Routes protected

- `PUT /conso-sheet/{id} -> ConsoSheetController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ConsoSheetPolicy.php` define `update` con `conso-sheet-update` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ConsoSheetController aplica el scope por organización con `conso-sheet-index` (no `conso-sheet-update`).

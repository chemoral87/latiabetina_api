# conso-sheet-delete

Permiso `conso-sheet-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-delete`)

## Routes protected

- `DELETE /conso-sheet/{id} -> ConsoSheetController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ConsoSheetPolicy.php` define `delete` con `conso-sheet-delete` (por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ConsoSheetController aplica el scope por organización con `conso-sheet-index` (no `conso-sheet-delete`).

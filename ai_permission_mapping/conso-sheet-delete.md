# conso-sheet-delete

Permiso `conso-sheet-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-delete`)

## Routes protected

- `DELETE /conso-sheet/{id} -> ConsoSheetController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ConsoSheetController aplica el scope por organización con `conso-sheet-index` (no `conso-sheet-delete`).

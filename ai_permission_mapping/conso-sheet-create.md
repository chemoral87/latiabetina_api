# conso-sheet-create

Permiso `conso-sheet-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-create`)

## Routes protected

- `POST /conso-sheet -> ConsoSheetController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ConsoSheetController aplica el scope por organización con `conso-sheet-index` (no `conso-sheet-create`).

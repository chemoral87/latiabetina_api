# conso-sheet-update

Permiso `conso-sheet-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-update`)

## Routes protected

- `PUT /conso-sheet/{id} -> ConsoSheetController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ConsoSheetController aplica el scope por organización con `conso-sheet-index` (no `conso-sheet-update`).

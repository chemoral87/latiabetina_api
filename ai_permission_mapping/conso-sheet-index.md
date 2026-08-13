# conso-sheet-index

Permiso `conso-sheet-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-index`)
- `app\Http\Controllers\ConsoSheetController.php` (`applyOrgPermissionScope` con `'conso-sheet-index'`)

## Routes protected

- `GET /conso-sheet -> ConsoSheetController@index`
- `GET /conso-sheet/{id} -> ConsoSheetController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)

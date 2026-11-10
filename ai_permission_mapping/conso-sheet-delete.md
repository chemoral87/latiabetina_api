# conso-sheet-delete

Permiso `conso-sheet-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-delete`)
- `app\Http\Controllers\ConsoSheetController.php` (`delete` resuelve el registro con `findInScope($id, 'conso-sheet-delete')`)

## Routes protected

- `DELETE /conso-sheet/{id} -> ConsoSheetController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `ConsoSheetController::findInScope()` — aplica `AppliesOrgPermissionScope` con `conso-sheet-delete` sobre el `org_id` del registro; fuera de alcance o inexistente → 404

> Nota: `app\Policies\ConsoSheetPolicy.php` define `delete` por org del registro, pero sigue sin invocarse; la exigencia efectiva es el middleware más `findInScope()`. Borrar un conso_sheet elimina en cascada sus `church_members` (`conso_sheet_id` con `cascadeOnDelete`).

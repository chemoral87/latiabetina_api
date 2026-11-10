# conso-sheet-update

Permiso `conso-sheet-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-update`)
- `app\Http\Controllers\ConsoSheetController.php` (`update` resuelve el registro con `findInScope($id, 'conso-sheet-update')` y valida `org_id` con `Rule::in($user->getOrgsByPermission('conso-sheet-update'))`)

## Routes protected

- `PUT /conso-sheet/{id} -> ConsoSheetController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `ConsoSheetController::findInScope()` — aplica `AppliesOrgPermissionScope` con `conso-sheet-update` sobre el `org_id` del registro; fuera de alcance o inexistente → 404
- Regla `Rule::in` en `org_id` — no se puede mover el registro a una org donde el usuario no tenga `conso-sheet-update` (422)

> Nota: `app\Policies\ConsoSheetPolicy.php` define `update` por org del registro, pero sigue sin invocarse; la exigencia efectiva es el middleware más `findInScope()`.

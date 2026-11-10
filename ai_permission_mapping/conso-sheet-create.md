# conso-sheet-create

Permiso `conso-sheet-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-create`)
- `app\Http\Controllers\ConsoSheetController.php` (`create` valida `org_id` con `Rule::in($user->getOrgsByPermission('conso-sheet-create'))`)

## Routes protected

- `POST /conso-sheet -> ConsoSheetController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `ConsoSheetController::create()` — la validación de `org_id` exige que la org destino esté entre las orgs del usuario con `conso-sheet-create` (422 si no); el scope de lectura del resto de métodos sigue siendo `conso-sheet-index`

> Nota: `app\Policies\ConsoSheetPolicy.php` define `create`/`createForOrg` con `conso-sheet-create`, pero sigue sin invocarse; la exigencia efectiva es el middleware más la regla `Rule::in` de `create()`.

# church-member-index

Permiso `church-member-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-index`)
- `app\Http\Controllers\ChurchMemberController.php` (`applyOrgPermissionScope` con `'conso-sheet-index'`; con `church-member-all` usa el scope de ese permiso)
- `app\Http\Controllers\ChurchMemberTrackingLogController.php` (`trackingLogs` usa `church-member-index` en middleware y scope de orgs con `'conso-sheet-index'`)

## Routes protected

- `GET /church-member -> ChurchMemberController@index`
- `GET /church-member/{id} -> ChurchMemberController@show`
- `GET /church-member/{id}/consolidator-logs -> ChurchMemberController@consolidatorLogs`
- `GET /church-member/{id}/tracking-logs -> ChurchMemberTrackingLogController@trackingLogs`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario; los controladores del módulo aplican el scope con `conso-sheet-index`)
- ⚠️ Capa latente: `app\Policies\ChurchMemberPolicy.php` define `viewAny`/`view` con `church-member-index` (más `church-member-all`), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: la lectura (index/show) se protege con `church-member-index`. Alta, edición y baja usan un solo permiso, `church-member-manage` (reemplaza `church-member-create`, `church-member-update` y `church-member-delete`). El scope por organización de los listados se aplica con `conso-sheet-index` (o `church-member-all` si el usuario lo tiene, vía `applyChurchMemberAllScope` / `hasChurchMemberAll`).

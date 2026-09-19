# conso-sheet-index

Permiso `conso-sheet-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:conso-sheet-index`)
- `app\Http\Controllers\ConsoSheetController.php` (`applyOrgPermissionScope` con `'conso-sheet-index'`; con `conso-sheet-all` amplía el scope a las orgs de ese permiso)
- `app\Http\Controllers\ChurchMemberController.php` (`applyOrgPermissionScope` con `'conso-sheet-index'` en `index`, `show` y `consolidatorLogs`; con `church-member-all` usa el scope de ese permiso)
- `app\Http\Controllers\ChurchMemberMedalController.php` (`applyOrgPermissionScope` con `'conso-sheet-index'`)
- `app\Http\Controllers\ChurchMemberTrackingLogController.php` (`applyOrgPermissionScope` con `'conso-sheet-index'`)

## Routes protected

- `GET /conso-sheet -> ConsoSheetController@index`
- `GET /conso-sheet/consolidators -> ConsoSheetController@consolidators`
- `GET /conso-sheet/{id} -> ConsoSheetController@show`
- `PUT /church-member/{id}/status -> ChurchMemberController@updateStatus`
- `GET /church-member/{id}/status-logs -> ChurchMemberController@statusLogs`
- `POST /church-member/{id}/tracking-logs -> ChurchMemberTrackingLogController@storeTrackingLog`
- `PUT /church-member/{id}/tracking-logs/{logId} -> ChurchMemberTrackingLogController@updateTrackingLog`
- `DELETE /church-member/{id}/tracking-logs/{logId} -> ChurchMemberTrackingLogController@deleteTrackingLog`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (filtra registros por los orgs del usuario para este permiso)
- ⚠️ Capa latente: `app\Policies\ConsoSheetPolicy.php` define `viewAny`/`view` con `conso-sheet-index` (y `create`/`update`/`delete` con los permisos `-create`/`-update`/`-delete`), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

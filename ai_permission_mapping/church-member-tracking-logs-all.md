# church-member-tracking-logs-all

Permiso `church-member-tracking-logs-all` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-tracking-logs-all`)
- `app\Http\Controllers\ChurchMemberTrackingLogController.php` (scope con `conso-sheet-index`, con fallback `church-member-all`)

## Routes protected

- `GET /church-member/tracking-logs/all -> ChurchMemberTrackingLogController@allTrackingLogs`
- `GET /church-member/tracking-logs/all/summary -> ChurchMemberTrackingLogController@allTrackingLogsSummary`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: a diferencia de `tracking-logs` (registros propios del usuario vía `church-member-tracking-logs-index`), estos endpoints devuelven la actividad de **todos** los usuarios, filtrada por organización con `conso-sheet-index` (o `church-member-all`).

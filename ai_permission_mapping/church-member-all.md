# church-member-all

Permiso `church-member-all` en la API. Es un permiso de "ampliación": no protege rutas por sí mismo, sino que amplía el scope de organizaciones dentro del módulo de miembros de iglesia.

## Files

- `app\Http\Controllers\ChurchMemberController.php` (`hasPermissionTo('church-member-all')` y `getOrgsByPermission('church-member-all')` vía `hasChurchMemberAll()` / `applyChurchMemberAllScope()`; usado en `index`, `show` y `consolidatorLogs`)
- `app\Http\Controllers\ChurchMemberMedalController.php` (fallback `church-member-all` antes de aplicar scope `conso-sheet-index`)
- `app\Http\Controllers\ChurchMemberTrackingLogController.php` (fallback `church-member-all` antes de aplicar scope `conso-sheet-index`)
- ⚠️ Capa latente: `app\Policies\ChurchMemberPolicy.php` usa `church-member-all` como ampliación en `view`/`createForOrg`/`update`/`delete`, pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos)

## Routes protected

_(ninguna directamente; afecta el scope de datos de las rutas protegidas por `church-member-index`, `church-member-consolidator-logs-index` y `church-member-tracking-logs-index`)_

## Enforced by

- `app\Http\Controllers\Concerns\AppliesOrgPermissionScope.php` (patrón de fallback: si el usuario tiene `church-member-all`, el listado se filtra por las orgs de ese permiso; si no, por las orgs de `conso-sheet-index`)

> Nota: sin `church-member-all`, los listados del módulo se filtran con `conso-sheet-index`; si el usuario tampoco tiene orgs para ese permiso, el resultado es vacío (`WHERE 1 = 0`). La documentación previa de `church-member-consolidator-logs-index` y `church-member-tracking-logs-index` ya describía este comportamiento.

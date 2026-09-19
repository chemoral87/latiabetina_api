# life-group-index

Permiso `life-group-index` en la API.

## Files

- `app\Http\Controllers\LifeGroupController.php` (`hasAnyPermission(['life-group-index'])`: si el usuario NO lo tiene, el listado se limita a sus grupos como líder o creados por él)

## Routes protected

_(ninguna vía middleware `permission_org`; las rutas `/life-groups` solo exigen autenticación JWT y el controlador filtra por líder/creador cuando falta el permiso)_

## Enforced by

- `app\Http\Controllers\LifeGroupController.php` (en `index`; verificado con `hasAnyPermission`, no con `getOrgsByPermission`)
- ⚠️ Capa latente: `app\Policies\LifeGroupPolicy.php` define `viewAny`/`view` con `life-group-index` (y `create`/`update`/`delete` con los permisos `-create`/`-update`/`-delete`), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la exigencia efectiva es solo el filtro del controlador.

> Nota: es el único permiso del proyecto que se comprueba en el controlador con `hasAnyPermission` en lugar del middleware `permission_org`. Sin el permiso, un líder solo ve sus grupos (`created_by` o `life_group_leaders`); con él, ve todos. Las rutas de reportes (`/life-groups/reports/*`) y el dashboard no aplican este permiso.

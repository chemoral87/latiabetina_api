# life-group-delete

Permiso `life-group-delete` en la API.

## Files

- `app\Policies\LifeGroupPolicy.php` (`delete` con `getOrgsByPermission('life-group-delete')` sobre la org del registro)

## Routes protected

_(ninguna vía middleware `permission_org`; `DELETE /life-groups/{id}` solo exige autenticación JWT)_

## Enforced by

- `app\Policies\LifeGroupPolicy.php` (⚠️ registrada en `AuthServiceProvider` pero sin invocar: no hay llamadas `->can()`/`Gate` ni `authorize()` activos; es una capa de control latente)

> Nota: hoy la eliminación de redes de vida no está restringida por permiso a nivel de ruta ni de controlador.

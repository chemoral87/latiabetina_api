# life-group-update

Permiso `life-group-update` en la API.

## Files

- `app\Policies\LifeGroupPolicy.php` (`update` con `getOrgsByPermission('life-group-update')` sobre la org del registro)

## Routes protected

_(ninguna vía middleware `permission_org`; `PUT /life-groups/{id}` solo exige autenticación JWT)_

## Enforced by

- `app\Policies\LifeGroupPolicy.php` (⚠️ registrada en `AuthServiceProvider` pero sin invocar: no hay llamadas `->can()`/`Gate` ni `authorize()` activos; es una capa de control latente)

> Nota: hoy la actualización de redes de vida no está restringida por permiso a nivel de ruta ni de controlador.

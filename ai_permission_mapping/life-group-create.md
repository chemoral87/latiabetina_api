# life-group-create

Permiso `life-group-create` en la API.

## Files

- `app\Policies\LifeGroupPolicy.php` (`create`/`createForOrg` con `getOrgsByPermission('life-group-create')`)
- `app\Http\Controllers\LifeGroupController.php` (comentario: un usuario que solo tiene `life-group-create` — líder — ve únicamente sus grupos)

## Routes protected

_(ninguna vía middleware `permission_org`; `POST /life-groups` solo exige autenticación JWT)_

## Enforced by

- `app\Policies\LifeGroupPolicy.php` (⚠️ registrada en `AuthServiceProvider` pero sin invocar: no hay llamadas `->can()`/`Gate` ni `authorize()` activos; es una capa de control latente)

> Nota: hoy la creación de redes de vida no está restringida por permiso a nivel de ruta; el efecto práctico de `life-group-create` es solo el filtro de "líder" en el listado de `LifeGroupController@index` (junto con la ausencia de `life-group-index`).

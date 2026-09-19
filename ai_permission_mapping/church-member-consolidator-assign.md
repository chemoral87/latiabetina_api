# church-member-consolidator-assign

Permiso `church-member-consolidator-assign` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-consolidator-assign`)
- `app\Http\Controllers\ChurchMemberController.php` (métodos `consolidators` y `syncConsolidators`)

## Routes protected

- `GET /church-member/{id}/consolidators -> ChurchMemberController@consolidators`
- `PUT /church-member/{id}/consolidators -> ChurchMemberController@syncConsolidators`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: es el permiso que usa el frontend (`latiabetina_nuxt4/app/pages/church-member/[id]/index.vue`) para mostrar/ocultar la asignación de consolidadores. No debe confundirse con `conso-sheet-consolidator-select` (ya sin uso en la API) ni con `church-member-consolidator-logs-index` (historial de asignaciones).

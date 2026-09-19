# assistance-dashboard-view

Permiso `assistance-dashboard-view` en la API.

## Files

_(Sin uso en código — el permiso ya no aparece en `routes/api.php` ni en ningún controlador. Existe solo como documento de mapeo y, potencialmente, como fila en la tabla `permissions`.)_

## Routes protected

_(ninguna)_

## Enforced by

_(ninguno)_

> Histórico: antes de que `GET /assistance/chart` pasara a requerir `assistance-index`, este permiso protegía el endpoint del dashboard de asistencias y `AssistanceController` aplicaba `applyOrgPermissionScope` con `'assistance-dashboard-view'`. Hoy `chart` usa `assistance-index` (scope en `AssistanceController@index`/`chart`).
>
> Referencia cruzada: el frontend (`latiabetina_nuxt4/app/pages/dashboard.vue`) sigue mostrando el widget de asistencias según un permiso con nombre distinto (`assitance-dashboard`, otro typo histórico), por lo que este permiso API podría volver a usarse si el widget consume `chart` de nuevo.

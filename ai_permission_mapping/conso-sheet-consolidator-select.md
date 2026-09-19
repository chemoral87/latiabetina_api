# conso-sheet-consolidator-select

Permiso `conso-sheet-consolidator-select` en la API.

## Files

_(Sin uso en la API — no aparece en `routes/api.php` ni en ningún controlador. Existe como documento de mapeo y como fila en la tabla `permissions`; el frontend lo usa para mostrar/ocultar el campo de consolidador.)_

## Routes protected

_(ninguna)_

## Enforced by

_(ninguno)_

> Histórico: `ConsoSheetController` validaba `consolidator_id` en `create`/`update` con este permiso (403 si se enviaba sin él). Hoy la asignación de consolidadores vive en `PUT /church-member/{id}/consolidators`, protegida por `church-member-consolidator-assign`.
>
> La búsqueda de usuarios consolidadores sigue en `GET /conso-sheet/consolidators`, protegido por `conso-sheet-index` (no por este permiso).
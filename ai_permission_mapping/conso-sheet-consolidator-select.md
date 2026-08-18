# conso-sheet-consolidator-select

Permiso `conso-sheet-consolidator-select` en la API.

## Files

- `app\Http\Controllers\ConsoSheetController.php` (validación de `consolidator_id` en `create`/`update`)

## Routes protected

- `POST /conso-sheet -> ConsoSheetController@create` (403 si se envía `consolidator_id` sin este permiso)
- `PUT /conso-sheet/{id} -> ConsoSheetController@update` (403 si se envía `consolidator_id` sin este permiso)

## Enforced by

- `app\Http\Controllers\ConsoSheetController.php` (`hasAnyPermission(['conso-sheet-consolidator-select'])`)

> Nota: la búsqueda de usuarios consolidadores usa `GET /conso-sheet/consolidators`, protegido por `conso-sheet-index` (no por este permiso).
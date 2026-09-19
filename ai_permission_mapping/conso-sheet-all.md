# conso-sheet-all

Permiso `conso-sheet-all` en la API. Es un permiso de "ampliación": no protege rutas por sí mismo, sino que amplía el scope de organizaciones en `ConsoSheetController`.

## Files

- `app\Http\Controllers\ConsoSheetController.php` (`hasPermissionTo('conso-sheet-all')` y `getOrgsByPermission('conso-sheet-all')` en `index`; con `conso-sheet-all` el listado se filtra por las orgs de ese permiso, si no, por las orgs de `conso-sheet-index`)

## Routes protected

_(ninguna directamente; afecta el scope de datos de `GET /conso-sheet` y de los endpoints que dependen de ese listado)_

## Enforced by

- `app\Http\Controllers\ConsoSheetController.php` (patrón de fallback: `conso-sheet-all` → orgs de ese permiso; si no, `conso-sheet-index`; sin orgs, resultado vacío)

> Nota: `show`, `create`, `update` y `delete` de consolidados no consultan `conso-sheet-all`; solo el listado (`index`) lo hace.

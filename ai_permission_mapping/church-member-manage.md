# church-member-manage

Permiso único de escritura de miembros de iglesia. Reemplaza `church-member-create`, `church-member-update` y `church-member-delete` para que un rol lleve un permiso en lugar de tres. Cubre alta, edición y baja. No cubre lectura (`church-member-index`), el flujo propio (`church-member-my-manage`) ni medallas.

## Files

- `routes\api.php` (middleware `permission_org:church-member-manage` en POST, PUT y DELETE)
- `app\Policies\ChurchMemberPolicy.php` (`create`, `createForOrg`, `update`, `delete`)

## Routes protected

- `POST /church-member -> ChurchMemberController@create`
- `PUT /church-member/{id} -> ChurchMemberController@update`
- `DELETE /church-member/{id} -> ChurchMemberController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ChurchMemberPolicy.php` define `create`/`createForOrg`/`update`/`delete` con `church-member-manage` (+ `church-member-all`), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ChurchMemberController aplica el scope por organización con `conso-sheet-index` (o `church-member-all`), no con `church-member-manage`.

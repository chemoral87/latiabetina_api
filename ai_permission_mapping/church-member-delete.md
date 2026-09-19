# church-member-delete

Permiso `church-member-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-delete`)

## Routes protected

- `DELETE /church-member/{id} -> ChurchMemberController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ChurchMemberPolicy.php` define `delete` con `church-member-delete` (+ `church-member-all`, por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ChurchMemberController aplica el scope por organización con `conso-sheet-index` (o `church-member-all`), no con `church-member-delete`.

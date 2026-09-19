# church-member-update

Permiso `church-member-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-update`)

## Routes protected

- `PUT /church-member/{id} -> ChurchMemberController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ChurchMemberPolicy.php` define `update` con `church-member-update` (+ `church-member-all`, por org del registro), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ChurchMemberController aplica el scope por organización con `conso-sheet-index` (o `church-member-all`), no con `church-member-update`.

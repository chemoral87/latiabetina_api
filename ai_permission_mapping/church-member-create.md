# church-member-create

Permiso `church-member-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-create`)

## Routes protected

- `POST /church-member -> ChurchMemberController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
- ⚠️ Capa latente: `app\Policies\ChurchMemberPolicy.php` define `create`/`createForOrg` con `church-member-create` (+ `church-member-all`), pero está registrada en `AuthServiceProvider` sin invocarse (no hay `->can()`, `Gate::` ni `authorize()` activos); hoy la única exigencia efectiva es el middleware.

> Nota: ChurchMemberController aplica el scope por organización con `conso-sheet-index` (o `church-member-all`), no con `church-member-create`.

# church-member-medal-create

Permiso `church-member-medal-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-medal-create`)

## Routes protected

- `POST /church-member/{id}/medals -> ChurchMemberMedalController@store`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ChurchMemberMedalController aplica el scope por organización con `conso-sheet-index` (o `church-member-all`), no con `church-member-medal-create`.

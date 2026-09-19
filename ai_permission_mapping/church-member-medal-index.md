# church-member-medal-index

Permiso `church-member-medal-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-medal-index`)
- `app\Http\Controllers\ChurchMemberMedalController.php` (scope con `conso-sheet-index`, con fallback `church-member-all`)

## Routes protected

- `GET /church-member/{id}/medals -> ChurchMemberMedalController@index`
- `GET /church-member/{id}/medal-logs -> ChurchMemberMedalController@logs`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: el scope por organización de las medallas se aplica con `conso-sheet-index` (o `church-member-all`), no con `church-member-medal-index`.

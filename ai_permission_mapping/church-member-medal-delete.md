# church-member-medal-delete

Permiso `church-member-medal-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:church-member-medal-delete`)

## Routes protected

- `DELETE /church-member/{id}/medals/{medalId} -> ChurchMemberMedalController@destroy`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: ChurchMemberMedalController aplica el scope por organización con `conso-sheet-index` (o `church-member-all`), no con `church-member-medal-delete`.

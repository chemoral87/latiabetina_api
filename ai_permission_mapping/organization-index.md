# organization-index

Permiso `organization-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:organization-index`)

## Routes protected

- `GET /organization -> OrganizationController@index`
- `GET /organization/filter -> OrganizationController@filter`
- `GET /organization/{id} -> OrganizationController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

# organization-index

Permiso `organization-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:organization-index`)

## Routes protected

- `GET /organization → OrganizationController@index`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

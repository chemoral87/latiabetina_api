# organization-filter

Permiso `organization-filter` en la API.

## Files

- `routes\api.php` (middleware `permission_org:organization-filter`)

## Routes protected

- `GET /organization/filter -> OrganizationController@filter`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)
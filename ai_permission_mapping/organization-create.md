# organization-create

Permiso `organization-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:organization-create`)

## Routes protected

- `POST /organization → OrganizationController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

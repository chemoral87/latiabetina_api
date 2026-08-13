# organization-update

Permiso `organization-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:organization-update`)

## Routes protected

- `PUT /organization/{id} -> OrganizationController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

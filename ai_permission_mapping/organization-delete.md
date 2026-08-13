# organization-delete

Permiso `organization-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:organization-delete`)

## Routes protected

- `DELETE /organization/{id} → OrganizationController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

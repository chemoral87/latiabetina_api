# whatsapp-index

Permiso `whatsapp-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:whatsapp-index`)

## Routes protected

- `GET /whatsapp/status -> WhatsAppController@status`
- `GET /whatsapp/logs -> WhatsAppController@logs`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: el envío y reenvío de mensajes requiere un permiso distinto: `whatsapp-send`.

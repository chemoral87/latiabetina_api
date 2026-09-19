# whatsapp-send

Permiso `whatsapp-send` en la API.

## Files

- `routes\api.php` (middleware `permission_org:whatsapp-send`)

## Routes protected

- `POST /whatsapp/send -> WhatsAppController@sendMessage`
- `POST /whatsapp/logs/{id}/resend -> WhatsAppController@resend`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: `whatsapp-index` solo permite consultar estado y logs; el envío y reenvío de mensajes requieren `whatsapp-send`.

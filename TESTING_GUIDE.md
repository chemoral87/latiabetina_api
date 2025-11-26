# Guía de Pruebas - Autenticación con Google

## Prerrequisitos

1. **Configuración de Google Cloud Console** (ver GOOGLE_AUTH_SETUP.md)
2. **Variables de entorno configuradas** en `.env`:
   ```
   GOOGLE_CLIENT_ID=tu_client_id
   GOOGLE_CLIENT_SECRET=tu_client_secret
   GOOGLE_REDIRECT_URI=http://localhost:8001/api/auth/google/callback
   ```

## Pruebas con Postman o cURL

### 1. Prueba de autenticación con token (recomendado para pruebas)

Para probar sin configurar un frontend completo, puedes usar el endpoint `/api/auth/google/token` con un token de Google:

#### Obtener un token de Google manualmente:

1. Ve a [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground/)
2. En la configuración (icono de engranaje), ingresa tu Client ID y Client Secret
3. Selecciona "Google OAuth2 API v2" → "https://www.googleapis.com/auth/userinfo.email"
4. Haz clic en "Authorize APIs"
5. Autoriza con tu cuenta de Google
6. Haz clic en "Exchange authorization code for tokens"
7. Copia el `access_token`

#### Prueba con cURL:

```bash
curl -X POST http://localhost:8001/api/auth/google/token \
  -H "Content-Type: application/json" \
  -d '{
    "token": "TU_ACCESS_TOKEN_AQUI"
  }'
```

#### Prueba con Postman:

```
POST http://localhost:8001/api/auth/google/token
Headers:
  Content-Type: application/json
Body (raw JSON):
{
  "token": "ya29.a0AfB_byDxxxxxxxxxxxxxxxxxx"
}
```

### 2. Prueba de flujo completo con redirección

Para probar el flujo completo con redirección (requiere un frontend):

1. Abre tu navegador en: `http://localhost:8001/api/auth/google/redirect`
2. Serás redirigido a Google para autenticarte
3. Después de autenticarte, Google te redirigirá al callback
4. El callback devolverá un JSON con el token JWT

## Respuesta esperada

```json
{
  "status": "success",
  "message": "Usuario autenticado con Google",
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Juan",
    "last_name": "Pérez",
    "email": "juan@gmail.com",
    "avatar": "https://lh3.googleusercontent.com/a/..."
  }
}
```

## Verificar la autenticación

Usa el token JWT recibido para acceder a rutas protegidas:

```bash
curl -X POST http://localhost:8001/api/auth/user \
  -H "Authorization: Bearer TU_JWT_TOKEN_AQUI"
```

O con Postman:

```
POST http://localhost:8001/api/auth/user
Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

## Verificar en la base de datos

```sql
-- Ver usuarios creados con Google
SELECT id, name, last_name, email, google_id, avatar, created_at 
FROM users 
WHERE google_id IS NOT NULL;
```

## Casos de prueba

### Caso 1: Nuevo usuario con Google
- **Input**: Token de una cuenta de Google nueva
- **Expected**: Se crea un nuevo usuario con `google_id` y sin `password` (o password aleatorio)
- **Verify**: `SELECT * FROM users WHERE email = 'email@gmail.com'`

### Caso 2: Usuario existente vincula cuenta de Google
- **Prerequisite**: Usuario registrado con email `test@gmail.com`
- **Input**: Token de Google con el mismo email
- **Expected**: Se actualiza el usuario existente agregando `google_id` y `avatar`
- **Verify**: Usuario ahora tiene `google_id` no nulo

### Caso 3: Login con Google de usuario ya vinculado
- **Prerequisite**: Usuario con `google_id` ya registrado
- **Input**: Token de Google del mismo usuario
- **Expected**: Login exitoso con JWT
- **Verify**: Token JWT válido y puede acceder a rutas protegidas

### Caso 4: Token inválido
- **Input**: Token expirado o inválido
- **Expected**: Error 500 con mensaje de error
- **Response**: 
  ```json
  {
    "status": "error",
    "message": "Error al autenticar con Google",
    "error": "Invalid token..."
  }
  ```

## Pruebas automatizadas (opcional)

Puedes crear tests PHPUnit para automatizar las pruebas:

```php
// tests/Feature/GoogleAuthTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_token_creates_new_user()
    {
        // Mock Socialite
        $this->mock(\Laravel\Socialite\Contracts\Factory::class, function ($mock) {
            $mock->shouldReceive('driver->stateless->userFromToken')
                ->andReturn(new \Laravel\Socialite\Two\User([
                    'id' => '123456789',
                    'email' => 'test@gmail.com',
                    'name' => 'Test User',
                    'avatar' => 'https://example.com/avatar.jpg',
                ]));
        });

        $response = $this->postJson('/api/auth/google/token', [
            'token' => 'fake-google-token'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'last_name', 'email', 'avatar']
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@gmail.com',
            'google_id' => '123456789'
        ]);
    }
}
```

## Troubleshooting

### Error: "Client authentication failed"
- Verifica que `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` sean correctos
- Asegúrate de que estén en el archivo `.env`
- Ejecuta `php artisan config:cache`

### Error: "redirect_uri_mismatch"
- Verifica que la URI de redirección en Google Cloud Console coincida exactamente con `GOOGLE_REDIRECT_URI`
- Debe incluir el protocolo (http/https), dominio y path completo

### Error: "Invalid token"
- El token de Google puede haber expirado
- Obtén un nuevo token desde OAuth Playground
- Verifica que estés usando el access_token correcto

### Usuario no se crea
- Verifica que la tabla `users` tenga las columnas `google_id` y `avatar`
- Ejecuta las migraciones: `php artisan migrate`
- Verifica los logs: `storage/logs/laravel.log`

## Logs útiles

Para debug, puedes revisar los logs de Laravel:

```bash
tail -f storage/logs/laravel.log
```

O agregar logging temporal en el controlador:

```php
\Log::info('Google User Data:', (array) $googleUser);
```

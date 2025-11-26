# Configuración de Autenticación con Google

## Configuración de Google Cloud Console

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita la API de Google+ (Google+ API)
4. Ve a "Credenciales" y crea credenciales de tipo "ID de cliente de OAuth 2.0"
5. Configura la pantalla de consentimiento OAuth
6. Agrega los URIs de redirección autorizados:
   - Para desarrollo local: `http://localhost:8001/api/auth/google/callback`
   - Para producción: `https://tu-dominio.com/api/auth/google/callback`

## Configuración del archivo .env

Agrega las siguientes variables a tu archivo `.env`:

```env
GOOGLE_CLIENT_ID=tu_google_client_id
GOOGLE_CLIENT_SECRET=tu_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8001/api/auth/google/callback
```

## Endpoints disponibles

### 1. Redirección a Google (Web)
```
GET /api/auth/google/redirect
```
Redirige al usuario a la página de autenticación de Google.

### 2. Callback de Google (Web)
```
GET /api/auth/google/callback
```
Google redirige aquí después de la autenticación.

### 3. Autenticación con Token (Mobile/SPA)
```
POST /api/auth/google/token
Content-Type: application/json

{
  "token": "google_access_token"
}
```
Para aplicaciones móviles o SPA que obtienen el token de Google directamente.

## Respuesta de autenticación exitosa

```json
{
  "status": "success",
  "message": "Usuario autenticado con Google",
  "access_token": "jwt_token_aqui",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Juan",
    "last_name": "Pérez",
    "email": "juan@gmail.com",
    "avatar": "https://lh3.googleusercontent.com/..."
  }
}
```

## Flujo de autenticación

### Para aplicaciones web:
1. Redirige al usuario a `/api/auth/google/redirect`
2. El usuario se autentica en Google
3. Google redirige a `/api/auth/google/callback`
4. La API devuelve el token JWT

### Para aplicaciones móviles/SPA:
1. La app obtiene el access token de Google usando sus SDKs nativos
2. Envía el token a `/api/auth/google/token`
3. La API valida el token con Google y devuelve el JWT

## Notas importantes

- Si un usuario ya existe con el mismo email, se vincula la cuenta de Google
- Si es un usuario nuevo, se crea automáticamente con los datos de Google
- El campo `password` es opcional para usuarios de Google (se genera uno aleatorio)
- El avatar de Google se guarda en el campo `avatar`
- El `google_id` se usa para identificar usuarios de Google

## Migración ejecutada

La migración `2025_11_26_033354_add_oauth_fields_to_users_table` agregó:
- `google_id`: Para almacenar el ID único de Google
- `avatar`: Para la URL del avatar del usuario
- `password`: Ahora es nullable para usuarios que solo usan Google

## Cambios en el modelo User

Se agregaron los campos `google_id` y `avatar` al array `$fillable`.

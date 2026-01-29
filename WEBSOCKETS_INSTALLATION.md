# 🎉 Laravel WebSockets - Instalación Completada

## ✅ Lo que se instaló

1. **Laravel WebSockets** (beyondcode/laravel-websockets v1.14.1)
2. **Dependencias necesarias**:
   - React PHP (event-loop, socket, http)
   - Pusher PHP Server
   - Ratchet WebSocket

## 🔧 Configuración realizada

### 1. Variables de entorno (.env)
```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=latiabetina
PUSHER_APP_KEY=latiabetina-key
PUSHER_APP_SECRET=latiabetina-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1
```

### 2. Archivos publicados
- ✅ `config/websockets.php` - Configuración de WebSockets
- ✅ `database/migrations/*_create_websockets_statistics_entries_table.php` - Migración ejecutada
- ✅ `app/Events/TestWebSocketEvent.php` - Evento de prueba creado

### 3. Servicios habilitados
- ✅ `App\Providers\BroadcastServiceProvider` - Descomentado en config/app.php

### 4. Archivos de prueba creados
- ✅ `public/test-websocket.html` - Cliente de prueba HTML
- ✅ `routes/api.php` - Ruta `/api/test-websocket` agregada

## 🚀 Cómo usar

### Paso 1: Iniciar el servidor WebSocket

Opción A - Desde terminal:
```bash
php artisan websockets:serve
```

Opción B - Desde VS Code:
1. Presiona `Ctrl+Shift+P`
2. Escribe "Tasks: Run Task"
3. Selecciona "WebSocket Server"

### Paso 2: Verificar que los servidores estén corriendo

Deberías tener 3 procesos corriendo:
1. **Servidor Laravel** (puerto 8001) - Ya está corriendo ✅
2. **Queue Listener** - Ya está corriendo ✅  
3. **WebSocket Server** (puerto 6001) - Iniciar ahora

### Paso 3: Probar WebSockets

1. Abre en tu navegador:
   ```
   http://localhost:8001/test-websocket.html
   ```

2. Verifica que diga "✅ Conectado al servidor WebSocket"

3. Escribe un mensaje y haz clic en "Enviar Evento"

4. Deberías ver el mensaje aparecer en tiempo real

### Paso 4: Acceder al Dashboard

El dashboard de WebSockets está disponible en:
```
http://localhost:8001/laravel-websockets
```

Aquí puedes:
- Ver conexiones activas
- Monitorear estadísticas
- Ver mensajes en tiempo real
- Depurar problemas

## 📝 Ejemplo de uso en tu aplicación

### Backend (Crear evento)

```php
// app/Events/MyCustomEvent.php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyCustomEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('my-channel');
    }
}
```

### Disparar evento desde controlador

```php
use App\Events\MyCustomEvent;

class MyController extends Controller
{
    public function sendNotification()
    {
        event(new MyCustomEvent('¡Nueva notificación!'));
        
        return response()->json(['status' => 'sent']);
    }
}
```

### Frontend (React/Next.js)

```bash
npm install --save pusher-js
```

```javascript
import Pusher from 'pusher-js';

// Configurar Pusher
const pusher = new Pusher('latiabetina-key', {
    wsHost: '127.0.0.1',
    wsPort: 6001,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});

// Suscribirse a un canal
const channel = pusher.subscribe('my-channel');

// Escuchar eventos
channel.bind('MyCustomEvent', (data) => {
    console.log('Mensaje recibido:', data.message);
    // Actualizar UI, mostrar notificación, etc.
});
```

### Frontend (Vanilla JS con Laravel Echo)

```bash
npm install --save laravel-echo pusher-js
```

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'latiabetina-key',
    wsHost: '127.0.0.1',
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
});

// Escuchar en canal público
Echo.channel('my-channel')
    .listen('MyCustomEvent', (e) => {
        console.log(e.message);
    });

// Escuchar en canal privado (requiere autenticación)
Echo.private(`user.${userId}`)
    .listen('PrivateEvent', (e) => {
        console.log(e);
    });
```

## 🔐 Canales Privados y de Presencia

### Configurar autenticación de canales

En `routes/channels.php`:

```php
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    // Verificar si el usuario puede acceder a esta sala
    return true;
});
```

### Uso en frontend con autenticación

```javascript
// Configurar Echo con autenticación
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'latiabetina-key',
    wsHost: '127.0.0.1',
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
    authEndpoint: 'http://localhost:8001/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': 'Bearer ' + token, // Tu JWT token
            'Accept': 'application/json',
        }
    }
});

// Canal privado
Echo.private('user.123')
    .listen('PrivateMessage', (e) => {
        console.log('Mensaje privado:', e);
    });
```

## 📊 Monitoreo y Depuración

### Ver logs del servidor WebSocket

Los logs se encuentran en:
```
storage/logs/laravel.log
```

### Comandos útiles

```bash
# Ver estadísticas
php artisan websockets:statistics

# Limpiar estadísticas antiguas
php artisan websockets:clean

# Reiniciar servidor (Ctrl+C y volver a iniciar)
php artisan websockets:serve
```

## ⚠️ Notas importantes para Windows

1. **Extensiones PHP no requeridas**: 
   - `pcntl` y `posix` no están disponibles en Windows
   - Laravel WebSockets funciona sin ellas en desarrollo

2. **Firewall**: 
   - Asegúrate de permitir el puerto 6001

3. **Para Producción**:
   - Usa un gestor de procesos como PM2
   - Configura SSL/TLS para conexiones seguras
   - Usa un proxy reverso (Nginx/Apache)

## 🆘 Solución de problemas

### "No se puede conectar al servidor WebSocket"

1. Verifica que el servidor esté corriendo:
   ```bash
   php artisan websockets:serve
   ```

2. Verifica el puerto en el dashboard:
   ```
   http://localhost:8001/laravel-websockets
   ```

3. Revisa los logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### "El evento se envía pero no se recibe"

1. Verifica la configuración en `.env`
2. Asegúrate de que el evento implemente `ShouldBroadcast`
3. Verifica que el canal sea público (`Channel`) o tengas autorización para privados (`PrivateChannel`)

### "CORS error"

Agrega el dominio del frontend en `config/websockets.php`:
```php
'allowed_origins' => [
    'http://localhost:3000',
    'http://localhost:3001',
],
```

## 📚 Recursos adicionales

- [Documentación oficial Laravel WebSockets](https://beyondco.de/docs/laravel-websockets)
- [Laravel Broadcasting](https://laravel.com/docs/10.x/broadcasting)
- [Pusher Protocol](https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol)

## ✨ ¡Listo para usar!

Tu instalación de Laravel WebSockets está completa y lista para usar. Puedes empezar a crear eventos en tiempo real para tu aplicación.

**Próximos pasos sugeridos:**
1. Crear eventos específicos para tu aplicación
2. Implementar notificaciones en tiempo real
3. Agregar chat en vivo
4. Implementar actualizaciones de estado en tiempo real

¡Disfruta de WebSockets en Laravel! 🚀

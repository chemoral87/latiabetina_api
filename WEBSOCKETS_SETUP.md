# Laravel WebSockets - Configuración para Windows

## ✅ Instalación Completada

Laravel WebSockets ha sido instalado y configurado correctamente.

## 📋 Configuración Actual

### Variables de entorno (.env)
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

## 🚀 Cómo Iniciar el Servidor WebSocket

### Opción 1: Comando directo
```bash
php artisan websockets:serve
```

### Opción 2: Con puerto específico
```bash
php artisan websockets:serve --port=6001
```

### Opción 3: Con host específico (para acceso remoto)
```bash
php artisan websockets:serve --host=0.0.0.0 --port=6001
```

## 🌐 Dashboard de WebSockets

Una vez iniciado el servidor, puedes acceder al dashboard en:
```
http://localhost:8001/laravel-websockets
```

## 📝 Uso Básico

### 1. Crear un Evento de Broadcasting

```bash
php artisan make:event MyEvent
```

### 2. Ejemplo de Evento
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

### 3. Disparar el Evento
```php
use App\Events\MyEvent;

event(new MyEvent('Hola desde WebSockets!'));
```

## 🔧 Configuración del Cliente (Frontend)

### Instalación de Pusher JS
```bash
npm install --save laravel-echo pusher-js
```

### Configuración en JavaScript
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'latiabetina-key',
    wsHost: '127.0.0.1',
    wsPort: 6001,
    wssPort: 6001,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});

// Escuchar eventos
window.Echo.channel('my-channel')
    .listen('MyEvent', (e) => {
        console.log(e.message);
    });
```

## ⚠️ Notas Importantes para Windows

1. **No requiere extensiones especiales**: En Windows, las extensiones `pcntl` y `posix` no están disponibles, pero Laravel WebSockets funciona sin ellas en modo de desarrollo.

2. **Firewall**: Asegúrate de que el puerto 6001 esté permitido en el firewall de Windows.

3. **Producción**: Para producción, se recomienda usar supervisord o pm2 para mantener el servidor WebSocket en ejecución:
   ```bash
   npm install -g pm2
   pm2 start "php artisan websockets:serve" --name websockets
   ```

## 🔍 Depuración

### Ver estadísticas en tiempo real
Accede al dashboard: `http://localhost:8001/laravel-websockets`

### Logs
Los eventos de WebSocket se registran en `storage/logs/laravel.log`

### Verificar conexión
```bash
# En el navegador o Postman
GET http://localhost:6001/app/latiabetina-key?protocol=7&client=js&version=4.3.1
```

## 📚 Recursos Adicionales

- [Documentación Oficial](https://beyondco.de/docs/laravel-websockets)
- [Laravel Broadcasting](https://laravel.com/docs/10.x/broadcasting)
- [Pusher Protocol](https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol)

## ⚡ Comandos Útiles

```bash
# Iniciar servidor WebSocket
php artisan websockets:serve

# Ver estadísticas
php artisan websockets:statistics

# Limpiar estadísticas
php artisan websockets:clean

# Reiniciar servidor (Ctrl+C y volver a ejecutar)
php artisan websockets:serve --port=6001
```

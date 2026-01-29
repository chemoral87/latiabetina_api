# 🪑 Integración de WebSocket para Asientos en Tiempo Real

## 📋 Evento Implementado

Se ha configurado el evento `SeatUpdated` que se dispara cuando se actualizan asientos en el sistema.

### 📍 Ubicaciones de archivos:

1. **Evento**: `app/Events/SeatUpdated.php`
2. **Controlador**: `app/Http/Controllers/AuditoriumEventSeatController.php`
3. **Monitor HTML**: `public/seat-monitor.html`

## 🔔 Cómo funciona

### Backend

El evento se dispara automáticamente en dos métodos del controlador:

1. **`store()` método** - Cuando se crean/actualizan asientos individuales
2. **`updateBatch()` método** - Cuando se actualizan múltiples asientos

```php
// El evento se dispara antes de retornar la respuesta
event(new SeatUpdated($updatedSeats, $auditoriumEventId));
```

### Canal de Broadcasting

El evento se transmite en un canal específico por evento:
```
auditorium-event.{auditorium_event_id}
```

Por ejemplo: `auditorium-event.1`, `auditorium-event.2`, etc.

### Datos enviados

```json
{
  "seats": [
    {
      "id": 1,
      "seat_id": "A1",
      "status": "ocupado",
      "auditorium_event_id": 1,
      "created_by": 1,
      ...
    }
  ],
  "auditorium_event_id": 1,
  "timestamp": "2026-01-29T10:30:00Z"
}
```

## 🧪 Probar la Integración

### 1. Iniciar el servidor WebSocket

```bash
php artisan websockets:serve
```

### 2. Abrir el monitor en el navegador

```
http://localhost:8001/seat-monitor.html
```

### 3. Suscribirse a un evento

1. Ingresa el ID del evento de auditorio (ej: 1)
2. Haz clic en "Suscribirse al Evento"

### 4. Actualizar asientos desde la API

```bash
# Usando curl o Postman
curl -X POST http://localhost:8001/api/auditorium-event-seats \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "auditorium_event_id": 1,
    "seat_ids": ["A1", "A2"],
    "status": "ocupado"
  }'
```

Verás la actualización en tiempo real en el monitor.

## 💻 Integración Frontend

### React / Next.js

#### 1. Instalar dependencias

```bash
npm install pusher-js
```

#### 2. Crear hook personalizado

```typescript
// hooks/useAuditoriumSeats.ts
import { useEffect, useState } from 'react';
import Pusher from 'pusher-js';

interface Seat {
  id: number;
  seat_id: string;
  status: string;
  auditorium_event_id: number;
  created_by: number;
}

interface SeatUpdateData {
  seats: Seat[];
  auditorium_event_id: number;
  timestamp: string;
}

export function useAuditoriumSeats(eventId: number) {
  const [seats, setSeats] = useState<Seat[]>([]);
  const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

  useEffect(() => {
    // Configurar Pusher
    const pusher = new Pusher('latiabetina-key', {
      wsHost: '127.0.0.1',
      wsPort: 6001,
      forceTLS: false,
      encrypted: false,
      disableStats: true,
      enabledTransports: ['ws', 'wss'],
    });

    // Suscribirse al canal del evento
    const channel = pusher.subscribe(`auditorium-event.${eventId}`);

    // Escuchar actualizaciones de asientos
    channel.bind('seat.updated', (data: SeatUpdateData) => {
      console.log('Asientos actualizados:', data);
      setSeats(data.seats);
      setLastUpdate(new Date(data.timestamp));
    });

    // Limpiar al desmontar
    return () => {
      channel.unbind_all();
      pusher.unsubscribe(`auditorium-event.${eventId}`);
      pusher.disconnect();
    };
  }, [eventId]);

  return { seats, lastUpdate };
}
```

#### 3. Usar en componente

```typescript
// components/AuditoriumSeatsMonitor.tsx
import React from 'react';
import { useAuditoriumSeats } from '@/hooks/useAuditoriumSeats';

interface Props {
  eventId: number;
}

export function AuditoriumSeatsMonitor({ eventId }: Props) {
  const { seats, lastUpdate } = useAuditoriumSeats(eventId);

  return (
    <div>
      <h2>Monitor de Asientos - Evento {eventId}</h2>
      
      {lastUpdate && (
        <p>Última actualización: {lastUpdate.toLocaleTimeString()}</p>
      )}

      <div className="seats-grid">
        {seats.map((seat) => (
          <div
            key={seat.id}
            className={`seat seat-${seat.status}`}
          >
            <span>{seat.seat_id}</span>
            <span>{seat.status}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
```

### Vue.js / Nuxt

```javascript
// composables/useAuditoriumSeats.js
import { ref, onMounted, onUnmounted } from 'vue';
import Pusher from 'pusher-js';

export function useAuditoriumSeats(eventId) {
  const seats = ref([]);
  const lastUpdate = ref(null);
  let pusher = null;
  let channel = null;

  onMounted(() => {
    pusher = new Pusher('latiabetina-key', {
      wsHost: '127.0.0.1',
      wsPort: 6001,
      forceTLS: false,
      encrypted: false,
      disableStats: true,
      enabledTransports: ['ws', 'wss'],
    });

    channel = pusher.subscribe(`auditorium-event.${eventId}`);

    channel.bind('seat.updated', (data) => {
      console.log('Asientos actualizados:', data);
      seats.value = data.seats;
      lastUpdate.value = new Date(data.timestamp);
    });
  });

  onUnmounted(() => {
    if (channel) {
      channel.unbind_all();
      pusher.unsubscribe(`auditorium-event.${eventId}`);
    }
    if (pusher) {
      pusher.disconnect();
    }
  });

  return { seats, lastUpdate };
}
```

### Angular

```typescript
// services/auditorium-seats.service.ts
import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import Pusher from 'pusher-js';

interface Seat {
  id: number;
  seat_id: string;
  status: string;
  auditorium_event_id: number;
  created_by: number;
}

@Injectable({
  providedIn: 'root'
})
export class AuditoriumSeatsService {
  private pusher: Pusher;
  private seatsSubject = new BehaviorSubject<Seat[]>([]);
  public seats$: Observable<Seat[]> = this.seatsSubject.asObservable();

  constructor() {
    this.pusher = new Pusher('latiabetina-key', {
      wsHost: '127.0.0.1',
      wsPort: 6001,
      forceTLS: false,
      encrypted: false,
      disableStats: true,
      enabledTransports: ['ws', 'wss'],
    });
  }

  subscribeToEvent(eventId: number): void {
    const channel = this.pusher.subscribe(`auditorium-event.${eventId}`);

    channel.bind('seat.updated', (data: any) => {
      console.log('Asientos actualizados:', data);
      this.seatsSubject.next(data.seats);
    });
  }

  unsubscribeFromEvent(eventId: number): void {
    this.pusher.unsubscribe(`auditorium-event.${eventId}`);
  }

  disconnect(): void {
    this.pusher.disconnect();
  }
}
```

## 🔐 Para Producción

### Usar canales privados (si necesitas autenticación)

1. **Cambiar el tipo de canal en el evento:**

```php
// app/Events/SeatUpdated.php
public function broadcastOn(): array
{
    return [
        new PrivateChannel('auditorium-event.' . $this->auditoriumEventId),
    ];
}
```

2. **Configurar autorización en routes/channels.php:**

```php
Broadcast::channel('auditorium-event.{eventId}', function ($user, $eventId) {
    // Verificar si el usuario puede acceder a este evento
    return true; // o tu lógica de autorización
});
```

3. **Configurar en el frontend:**

```javascript
const pusher = new Pusher('latiabetina-key', {
    wsHost: 'tu-dominio.com',
    wsPort: 6001,
    forceTLS: true,
    encrypted: true,
    authEndpoint: 'https://tu-api.com/broadcasting/auth',
    auth: {
        headers: {
            'Authorization': 'Bearer ' + yourJWTToken,
            'Accept': 'application/json',
        }
    }
});
```

## 📊 Dashboard de WebSockets

Para monitorear conexiones y mensajes en tiempo real:

```
http://localhost:8001/laravel-websockets
```

## 🆘 Solución de Problemas

### El evento no se recibe

1. Verifica que el servidor WebSocket esté corriendo
2. Revisa la consola del navegador para errores
3. Verifica que el `BROADCAST_DRIVER` sea `pusher` en `.env`
4. Asegúrate de estar suscrito al canal correcto

### Ver los eventos que se están enviando

```bash
# En el dashboard de WebSockets
http://localhost:8001/laravel-websockets

# O en los logs
tail -f storage/logs/laravel.log
```

## ✨ Casos de Uso

Esta integración es perfecta para:

- ✅ Actualización en tiempo real de disponibilidad de asientos
- ✅ Mostrar qué asientos están siendo reservados por otros usuarios
- ✅ Notificaciones instantáneas de cambios de estado
- ✅ Sincronización entre múltiples dispositivos
- ✅ Prevenir doble reserva mostrando cambios al instante

## 🚀 ¡Todo Listo!

El sistema de notificaciones en tiempo real para asientos está completamente configurado y listo para usar. Cualquier actualización de asientos se transmitirá automáticamente a todos los clientes suscritos al evento correspondiente.

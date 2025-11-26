# Ejemplo de implementación en el Frontend

## HTML + JavaScript (Web App)

### Opción 1: Usando redirección
```html
<!DOCTYPE html>
<html>
<head>
    <title>Login con Google</title>
</head>
<body>
    <h1>Autenticación con Google</h1>
    
    <!-- Botón de login con Google -->
    <button onclick="loginWithGoogle()">
        <img src="https://developers.google.com/identity/images/btn_google_signin_light_normal_web.png" alt="Sign in with Google">
    </button>

    <script>
        function loginWithGoogle() {
            // Redirigir a la URL de autenticación de Google
            window.location.href = 'http://localhost:8001/api/auth/google/redirect';
        }
        
        // Cuando Google redirige de vuelta, capturar el token
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const accessToken = urlParams.get('access_token');
            
            if (accessToken) {
                // Guardar el token
                localStorage.setItem('access_token', accessToken);
                console.log('Token guardado:', accessToken);
                
                // Obtener información del usuario
                getUserInfo(accessToken);
            }
        }
        
        function getUserInfo(token) {
            fetch('http://localhost:8001/api/auth/user', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Usuario:', data);
                // Actualizar la UI con los datos del usuario
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
```

### Opción 2: Usando Google Sign-In SDK (Recomendado para SPA)
```html
<!DOCTYPE html>
<html>
<head>
    <title>Login con Google</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <h1>Autenticación con Google</h1>
    
    <!-- Botón de Google Sign-In -->
    <div id="g_id_onload"
         data-client_id="TU_GOOGLE_CLIENT_ID"
         data-callback="handleCredentialResponse">
    </div>
    <div class="g_id_signin" data-type="standard"></div>

    <script>
        function handleCredentialResponse(response) {
            // response.credential es el JWT de Google
            const googleToken = response.credential;
            
            // Enviar el token a tu backend
            fetch('http://localhost:8001/api/auth/google/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: googleToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Guardar el token JWT de tu aplicación
                    localStorage.setItem('access_token', data.access_token);
                    console.log('Login exitoso:', data.user);
                    
                    // Redirigir o actualizar la UI
                    window.location.href = '/dashboard';
                } else {
                    console.error('Error en el login:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
```

## React Example

```jsx
import React, { useEffect } from 'react';
import { GoogleLogin } from '@react-oauth/google';
import { GoogleOAuthProvider } from '@react-oauth/google';

function App() {
  const handleGoogleLogin = async (credentialResponse) => {
    try {
      const response = await fetch('http://localhost:8001/api/auth/google/token', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token: credentialResponse.credential
        }),
      });

      const data = await response.json();

      if (data.status === 'success') {
        // Guardar el token
        localStorage.setItem('access_token', data.access_token);
        console.log('Login exitoso:', data.user);
        // Redirigir o actualizar el estado
      }
    } catch (error) {
      console.error('Error al autenticar:', error);
    }
  };

  return (
    <GoogleOAuthProvider clientId="TU_GOOGLE_CLIENT_ID">
      <div>
        <h1>Login con Google</h1>
        <GoogleLogin
          onSuccess={handleGoogleLogin}
          onError={() => {
            console.log('Login Failed');
          }}
        />
      </div>
    </GoogleOAuthProvider>
  );
}

export default App;
```

## Vue.js Example

```vue
<template>
  <div>
    <h1>Login con Google</h1>
    <button @click="loginWithGoogle">
      <img src="https://developers.google.com/identity/images/btn_google_signin_light_normal_web.png" alt="Sign in with Google">
    </button>
  </div>
</template>

<script>
export default {
  mounted() {
    // Cargar el script de Google
    this.loadGoogleScript();
  },
  methods: {
    loadGoogleScript() {
      const script = document.createElement('script');
      script.src = 'https://accounts.google.com/gsi/client';
      script.async = true;
      script.defer = true;
      document.head.appendChild(script);
      
      script.onload = () => {
        google.accounts.id.initialize({
          client_id: 'TU_GOOGLE_CLIENT_ID',
          callback: this.handleCredentialResponse
        });
      };
    },
    loginWithGoogle() {
      google.accounts.id.prompt();
    },
    async handleCredentialResponse(response) {
      try {
        const res = await fetch('http://localhost:8001/api/auth/google/token', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            token: response.credential
          }),
        });

        const data = await res.json();

        if (data.status === 'success') {
          localStorage.setItem('access_token', data.access_token);
          console.log('Login exitoso:', data.user);
          this.$router.push('/dashboard');
        }
      } catch (error) {
        console.error('Error al autenticar:', error);
      }
    }
  }
}
</script>
```

## Angular Example

```typescript
// app.component.ts
import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';

declare const google: any;

@Component({
  selector: 'app-root',
  template: `
    <h1>Login con Google</h1>
    <div id="g_id_onload"
         data-client_id="TU_GOOGLE_CLIENT_ID"
         data-callback="handleCredentialResponse">
    </div>
    <div class="g_id_signin" data-type="standard"></div>
  `
})
export class AppComponent implements OnInit {
  constructor(private http: HttpClient) {}

  ngOnInit() {
    // Cargar el script de Google
    const script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);

    // Hacer la función global para que Google pueda llamarla
    (window as any).handleCredentialResponse = this.handleCredentialResponse.bind(this);
  }

  handleCredentialResponse(response: any) {
    this.http.post('http://localhost:8001/api/auth/google/token', {
      token: response.credential
    }).subscribe({
      next: (data: any) => {
        if (data.status === 'success') {
          localStorage.setItem('access_token', data.access_token);
          console.log('Login exitoso:', data.user);
          // Redirigir o actualizar el estado
        }
      },
      error: (error) => {
        console.error('Error al autenticar:', error);
      }
    });
  }
}
```

## React Native / Flutter (Mobile)

Para aplicaciones móviles, utiliza los paquetes oficiales de Google Sign-In:

### React Native
```javascript
import { GoogleSignin } from '@react-native-google-signin/google-signin';

// Configurar
GoogleSignin.configure({
  webClientId: 'TU_GOOGLE_CLIENT_ID',
});

// Login
const signInWithGoogle = async () => {
  try {
    await GoogleSignin.hasPlayServices();
    const userInfo = await GoogleSignin.signIn();
    
    // Obtener el token de acceso
    const tokens = await GoogleSignin.getTokens();
    
    // Enviar a tu backend
    const response = await fetch('http://localhost:8001/api/auth/google/token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        token: tokens.accessToken
      }),
    });
    
    const data = await response.json();
    // Guardar el token JWT
  } catch (error) {
    console.error(error);
  }
};
```

### Flutter
```dart
import 'package:google_sign_in/google_sign_in.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

final GoogleSignIn _googleSignIn = GoogleSignIn(
  scopes: ['email'],
);

Future<void> signInWithGoogle() async {
  try {
    final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
    final GoogleSignInAuthentication googleAuth = await googleUser!.authentication;
    
    // Enviar el token a tu backend
    final response = await http.post(
      Uri.parse('http://localhost:8001/api/auth/google/token'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'token': googleAuth.accessToken,
      }),
    );
    
    final data = jsonDecode(response.body);
    
    if (data['status'] == 'success') {
      // Guardar el token JWT
      final jwtToken = data['access_token'];
      // Guardar en secure storage
    }
  } catch (error) {
    print(error);
  }
}
```

## Uso del token JWT

Una vez que obtengas el token JWT de tu backend, úsalo en todas las peticiones:

```javascript
// JavaScript/TypeScript
fetch('http://localhost:8001/api/user', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

## Notas importantes

1. Reemplaza `TU_GOOGLE_CLIENT_ID` con tu Client ID real de Google Cloud Console
2. Para aplicaciones móviles, necesitarás configurar SHA-1/SHA-256 en Google Cloud Console
3. El token JWT debe enviarse en el header `Authorization: Bearer {token}` para las rutas protegidas
4. Considera implementar refresh token para mantener la sesión activa

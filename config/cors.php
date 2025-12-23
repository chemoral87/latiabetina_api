<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Cross-Origin Resource Sharing (CORS) Configuration
  |--------------------------------------------------------------------------
  |
  | Here you may configure your settings for cross-origin resource sharing
  | or "CORS". This determines what cross-origin operations may execute
  | in web browsers. You are free to adjust these settings as needed.
  |
  | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
  |
   */

  'paths' => ['api/*', 'sanctum/csrf-cookie'],

  'allowed_methods' => ['*'],

  'allowed_origins' => [
    'https://admin.latiabetina.com', // ⭐ Tu frontend real
    'https://latiabetina.com',
    'https://www.latiabetina.com',
    'https://admin.avivamientomonterrey.com', // Admin externo que solicita login
  ],

  'allowed_origins_patterns' => [
    '/^http:\/\/localhost:\d+$/',
    '/^https:\/\/.*\.latiabetina\.com$/',
  ],

  'allowed_headers' => ['*'],

  'exposed_headers' => [],

  'max_age' => 0,

  'supports_credentials' => true,

];

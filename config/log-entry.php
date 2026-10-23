<?php

return [
    // Máximo de caracteres del `context` devuelto por /log-entry.
    // El recorte lo hace el backend; el frontend solo muestra lo recibido.
    'context_max_chars' => (int) env('LOG_ENTRY_CONTEXT_MAX_CHARS', 1000),
];

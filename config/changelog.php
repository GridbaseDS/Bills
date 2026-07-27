<?php

return [
    'version' => '3.5.4',
    'date' => '2026-07-27',
    'changes' => [
        'Autenticación Biométrica (Face ID / Passkeys): Inicio de sesión instantáneo de 0 clics. Al cargar la pantalla de login en dispositivos autorizados, el scanner de Face ID / Touch ID se activa automáticamente.',
        'Autenticación Biométrica (Face ID / Passkeys): Manejo amigable de excepciones nativas de iOS/Safari confirmando cuando la llave ya existe en iCloud Keychain.',
        'Búsqueda Inteligente RNC / Cédula: Reconocimiento inteligente con temporizador de pausa (debounce 450ms), reintento dinámico al borrar/editar y búsqueda combinada en DGII y JCE Cédula.',
        'Buscador Spotlight (⌘K / Ctrl+K): Ventana flotante de comandos con desenfoque de fondo (backdrop blur), accesos directos y búsqueda en tiempo real.'
    ]
];

<?php

return [
    'version' => '3.5.3',
    'date' => '2026-07-27',
    'changes' => [
        'Autenticación Biométrica (Face ID / Passkeys): Manejo amigable de excepciones nativas de iOS/Safari (InvalidStateError) confirmando cuando Face ID ya se encuentra activo en el iPhone.',
        'Autenticación Biométrica (Face ID / Passkeys): Corrección en la consulta de llaves biométricas para permitir el inicio de sesión con Face ID en iOS / iPhone independientemente de sesiones o tokens previos.',
        'Búsqueda Inteligente RNC / Cédula: Reconocimiento inteligente con temporizador de pausa (debounce 450ms), reintento dinámico al borrar/editar y búsqueda combinada en DGII y JCE Cédula.',
        'Buscador Spotlight (⌘K / Ctrl+K): Ventana flotante de comandos con desenfoque de fondo (backdrop blur), accesos directos y búsqueda en tiempo real.'
    ]
];

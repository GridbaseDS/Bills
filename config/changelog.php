<?php

return [
    'version' => '3.5.2',
    'date' => '2026-07-27',
    'changes' => [
        'Autenticación Biométrica (Face ID / Passkeys): Corrección en la consulta de llaves biométricas para permitir el inicio de sesión con Face ID en iOS / iPhone independientemente de sesiones o tokens previos.',
        'Búsqueda Inteligente RNC / Cédula: Reconocimiento inteligente con temporizador de pausa (debounce 450ms), reintento dinámico al borrar/editar y búsqueda combinada en DGII y JCE Cédula.',
        'Buscador Spotlight (⌘K / Ctrl+K): Ventana flotante de comandos con desenfoque de fondo (backdrop blur), accesos directos y búsqueda en tiempo real.',
        'Diseño Responsivo en Configuración: Corrección de desbordamiento horizontal en las pestañas y restauración del padding.'
    ]
];

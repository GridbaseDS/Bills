<?php

return [
    'version' => '3.5.5',
    'date' => '2026-07-27',
    'changes' => [
        'Impresión de Tickets Térmicos (2Connect POS80-01 V7): Adaptación exacta de formato en 80mm (72mm imprimibles), tipografía monoespaciada de alta nitidez (203 DPI) y margen de corte automático.',
        'Integración POS Cardnet (Saturn 1000): Optimización del driver Cardnet Android SmartPOS REST para terminales PAX Saturn 1000 y soporte de impresión en formato 58mm.',
        'Autenticación Biométrica (Face ID / Passkeys): Inicio de sesión instantáneo de 0 clics activando Face ID / Touch ID al cargar la pantalla.',
        'Búsqueda Inteligente RNC / Cédula: Reconocimiento con pausa (debounce 450ms), reintento dinámico y búsqueda combinada en DGII y JCE Cédula.'
    ]
];

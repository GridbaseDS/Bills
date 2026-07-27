<?php

return [
    'version' => '3.5.7',
    'date' => '2026-07-27',
    'changes' => [
        'Integración POS Cardnet (Saturn 1000): Corrección en el formato del monto enviado al terminal Android Saturn 1000 para transmitir el total exacto en pesos (ej. 1,770.00 DOP) evitando la multiplicación x100.',
        'Configuración & Navegación: Reestructuración de pestañas con nombres claros "Apariencia & Impresión" y "POS & Verifone" para acceso directo a plantillas térmicas y verifones.',
        'Impresión de Tickets Térmicos (2Connect POS80-01 V7): Adaptación exacta de formato en 80mm (72mm imprimibles), tipografía monoespaciada de alta nitidez (203 DPI) y margen de corte automático.'
    ]
];

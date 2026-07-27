<?php

return [
    'version' => '3.5.9',
    'date' => '2026-07-27',
    'changes' => [
        'Integración POS Cardnet (Saturn 1000): Envió de monto en tipo numérico flotante en pesos (ej. 1770) evitando formateo de string con punto ("1770.00") que provocaba que el POS removiera el punto y multiplicara el valor por 100.',
        'BillsBridge Local & Cardnet Saturn 1000: Sincronización del payload numérico exacto en la petición HTTP POST hacia la terminal Android.',
        'Impresión de Tickets Térmicos (2Connect POS80-01 V7): Adaptación de formato en 80mm (72mm imprimibles) con margen de corte automático.'
    ]
];

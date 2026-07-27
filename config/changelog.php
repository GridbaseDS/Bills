<?php

return [
    'version' => '3.5.8',
    'date' => '2026-07-27',
    'changes' => [
        'BillsBridge Local & Cardnet Saturn 1000: Corrección crítica en el cuerpo del payload JSON en req.write() dentro de BillsBridge para enviar el monto exacto en pesos ("1770.00") en lugar de la variable de centavos obsoleta.',
        'Integración POS Cardnet (Saturn 1000): Corrección en el formato del monto enviado al terminal Android Saturn 1000 para transmitir el total exacto en pesos (ej. 1,770.00 DOP) evitando la multiplicación x100.',
        'Impresión de Tickets Térmicos (2Connect POS80-01 V7): Adaptación exacta de formato en 80mm (72mm imprimibles), tipografía monoespaciada de alta nitidez (203 DPI) y margen de corte automático.'
    ]
];

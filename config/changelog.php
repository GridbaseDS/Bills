<?php

return [
    'version' => '3.6.0',
    'date' => '2026-07-27',
    'changes' => [
        'Impresión & PDF: "Ver PDF" siempre genera la versión estándar A4 / Carta de alta resolución independientemente del formato de ticket configurado.',
        'Impresión & PDF: "Ticket Térmico" muestra y descarga la versión optimizada de ticket en 80mm (2Connect POS80-01 V7) o 58mm (Saturn 1000).',
        'Auto-Impresión al Pagar: Nueva opción en Ajustes para disparar la impresión automática del ticket térmico a la impresora por defecto tan pronto se registra un pago.',
        'Integración POS Cardnet (Saturn 1000): Formateo de monto flotante directo en pesos (ej. 1770) evitando escalado por eliminación de puntos.'
    ]
];

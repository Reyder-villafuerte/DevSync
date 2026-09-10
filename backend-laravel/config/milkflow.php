<?php

// Parámetros de negocio de la planta. Se externalizan aquí para que un cambio
// de política (p. ej. el % de descuento por adulteración) NO exija tocar los
// Services ni una migración, y para que queden versionados y auditables.
return [
    // --- RN-05: adulteración con agua ---
    'agua' => [
        'umbral_expulsion_pct' => 5.0,       // >= 5% => expulsión inmediata
        'descuento_leve_pct' => 0.15,        // < 5% 1ra vez: 15% del bruto semanal
        'descuento_reincidente_pct' => 0.30, // < 5% reincidente: 30% del bruto
    ],

    // --- RN-06: acidez ---
    'acidez' => [
        'ph_minimo' => 6.5,                  // pH < 6.5 => rechazo + capacitación
    ],

    // --- RN-08: rendimiento quesero ---
    'rendimiento' => [
        'min_por_100l' => 11.0,
        'max_por_100l' => 12.0,
        'bloquea_cierre_si_incumple' => false, // la producción física ya ocurrió: solo se alerta
    ],

    // --- Tolerancia volumétrica acopiador vs. caudalímetro ---
    'conciliacion' => [
        'tolerancia_pct' => 1.0,             // diferencia > 1% => alerta
    ],

    // --- Verificación de recepción POR ENTREGA (jefe de producción) ---
    'recepcion' => [
        // Diferencia en LITROS que se tolera entre lo declarado por el acopiador
        // y lo confirmado por el jefe antes de marcar la entrega 'faltante' /
        // 'excedente'. 0 = exige coincidencia exacta.
        'tolerancia_litros' => 0.0,
    ],

    // --- Stock ---
    'stock' => [
        'permitir_negativo' => false,        // egresos que dejarían stock < 0 se rechazan
    ],

    // --- Facturación ---
    'facturacion' => [
        'tamano_bloque_default' => 50,       // números reservados por dispositivo
    ],
];

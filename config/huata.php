<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Códigos heredados del almacén
    |--------------------------------------------------------------------------
    |
    | De cuando la planta solo sabía hacer queso. Varias pantallas todavía
    | consultan estas existencias directo por código; sacarlas del código del
    | todo es la última fase del plan de catálogo abierto.
    |
    | Mientras tanto viven aquí y no repartidos en literales por toda la app:
    | la venta los usa solo para entender los pedidos viejos del móvil, que
    | mandan «cheese_molds_quantity» sin decir qué producto es.
    |
    */

    'codigos' => [
        'leche' => env('HUATA_CODIGO_LECHE', 'MILK_RAW_LITERS'),
        'queso' => env('HUATA_CODIGO_QUESO', 'CHEESE_MOLD_UNITS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles del padrón
    |--------------------------------------------------------------------------
    |
    | Los nueve roles del sistema, con el nombre que ve la gente. Un tipo de
    | cliente puede atarse a uno de ellos para reconocer su tarifa solo: el
    | proveedor de leche hoy, el descuento de empleados mañana.
    |
    */

    'roles' => [
        'productor' => 'Productor de leche',
        'acopiador' => 'Acopiador',
        'jefe_produccion' => 'Jefe de producción',
        'inspector_calidad' => 'Inspector de calidad',
        'personal_venta' => 'Personal de venta',
        'personal_pago' => 'Personal de pago',
        'pagador_campo' => 'Pagador de campo',
        'admin' => 'Administrador',
        'jefe_general' => 'Jefe general',
    ],

];

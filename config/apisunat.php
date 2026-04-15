<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Sunat Configuration (Apisunat)
    |--------------------------------------------------------------------------
    |
    | Aquí se definen los parámetros por defecto para la integración con Apisunat.
    | La URL base, el ID de persona y el token por defecto.
    |
    */

    'url'   => env('APISUNAT_URL', 'https://back.apisunat.com'),
    'id'    => env('APISUNAT_ID'),
    'token' => [
        'prod' => env('APISUNAT_TOKEN_PROD'),
    ],
    'series' => [
        'boleta'   => env('APISUNAT_SERIES_BOLETA', 'B001'),
        'factura'  => env('APISUNAT_SERIES_FACTURA', 'F001'),
    ],

    /*
    | Datos del emisor para PDF local y referencia (alinear con facturación electrónica).
    */
    'company' => [
        'ruc' => env('COMPANY_RUC', '20100100100'),
        'legal_name' => env('COMPANY_LEGAL_NAME', 'SUBUZ SAC'),
        'address' => env('COMPANY_ADDRESS', ''),
    ],
];

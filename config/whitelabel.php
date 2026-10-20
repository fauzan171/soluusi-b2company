<?php

// White-label config (PRD §15.4). Customer TIDAK boleh tahu service dari Soluusi.
// Default neutral — per-klinik override via kliniks table (brand, logo, email).
return [
    'default_brand' => env('WHITE_LABEL_DEFAULT_BRAND', 'Klinik Konsultan'),
    // Brand untuk komunikasi internal (konsultan recruitment — konsultan =
    // vendor Soluusi, bukan customer; white-label hanya wajib ke customer).
    'internal_brand' => env('WHITE_LABEL_INTERNAL_BRAND', 'Soluusi'),
    'domain' => env('WHITE_LABEL_DOMAIN', 'localhost:8000'),
    'email' => env('MAIL_FROM_ADDRESS', 'no-reply@klinik-brand.id'),
    'email_name' => env('MAIL_FROM_NAME', env('WHITE_LABEL_DEFAULT_BRAND', 'Klinik Konsultan')),
    'logo' => env('WHITE_LABEL_LOGO'),
];

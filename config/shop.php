<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Coordonnees de la boutique
    |--------------------------------------------------------------------------
    |
    | Ces valeurs sont partagees avec le front-end (voir
    | App\Providers\AppServiceProvider) et utilisees pour construire
    | le lien WhatsApp Click-to-Chat.
    |
    */

    'name' => env('APP_NAME', 'Ma Boutique'),

    'phone' => env('SHOP_PHONE', ''),

    'email' => env('SHOP_EMAIL', ''),

    'address' => env('SHOP_ADDRESS', ''),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp
    |--------------------------------------------------------------------------
    |
    | Numero du vendeur au format international sans "+" ni espaces
    | (ex. 237679603514). Le driver "click_to_chat" redirige le client vers
    | wa.me ; le driver "business_api" est reserve a une integration future de
    | la WhatsApp Business API (voir App\Services\WhatsAppService).
    |
    */

    'whatsapp' => [
        'driver' => env('SHOP_WHATSAPP_DRIVER', 'click_to_chat'),
        'number' => preg_replace('/\D/', '', (string) env('SHOP_WHATSAPP_NUMBER', '237679603514')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Devise
    |--------------------------------------------------------------------------
    */

    'currency' => env('SHOP_CURRENCY', 'FCFA'),

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    |
    | low_stock_threshold : seuil d'alerte "stock faible" du dashboard.
    | max_line_quantity   : garde-fou sur la quantite d'une ligne de panier.
    |
    */

    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),

    'max_line_quantity' => (int) env('SHOP_MAX_LINE_QUANTITY', 99),
    'pending_order_expiration_hours' => (int) env('SHOP_PENDING_ORDER_EXPIRATION_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Packs personnalises
    |--------------------------------------------------------------------------
    |
    | Nombre de produits distincts autorises dans un pack cree par le client.
    |
    */

    'custom_pack' => [
        'min_products' => (int) env('SHOP_CUSTOM_PACK_MIN', 2),
        'max_products' => (int) env('SHOP_CUSTOM_PACK_MAX', 20),
    ],

];

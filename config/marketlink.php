<?php

// demo se pehle jaldi badalne wali settings
return [

    'currency_symbol' => env('MARKETLINK_CURRENCY', '$'),

    // ab sirf fallback - map khud pins ke hisaab se fit hota hai (market-map.js). Poora Texas dikhta hai
    'default_map_center' => [
        'latitude' => 31.9686,
        'longitude' => -99.9018,
        'zoom' => 6,
    ],

    // MarketLink office - Contact page aur footer dono yahin se parhte hain
    'office_location' => [
        'latitude' => 32.7880,
        'longitude' => -96.8000,
        'address' => '2100 Ross Ave, Suite 800, Dallas, TX 75201',
        'city_label' => 'Dallas, Texas',
        'phone' => '(214) 555-0100',
        'phone_dial' => '+12145550100',
    ],

    // farmer apne profile se badal sakta hai
    'default_order_cutoff_hours' => 12,

    'low_stock_threshold' => 5,

    // product form ke unit dropdown mein isi order mein
    'product_units' => ['kg', 'dozen', 'bunch', 'piece', 'litre', 'pack'],

    // login page pe judges ke liye "Enter as customer/farmer/admin" buttons - default off
    'show_demo_logins' => env('MARKETLINK_SHOW_DEMO_LOGINS', false),
];

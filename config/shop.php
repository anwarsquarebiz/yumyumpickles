<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Catalog prices, carts and orders are persisted in the base currency as
    | integers in minor units (for example cents). "decimals" controls how
    | many minor units make up one major unit and is used by App\Support\Money
    | when converting to and from decimals.
    |
    | Storefront display amounts are converted from the base using exchange
    | rates. The visitor's default display currency is inferred from IP
    | country and can be overridden from the header switcher.
    |
    */

    'currency' => [
        'code' => env('SHOP_CURRENCY', 'INR'),
        'decimals' => 2,
        'session_key' => 'display_currency',
        'cookie' => 'currency',
        'cookie_minutes' => 525600,
        'free_shipping_threshold_amount' => 49900,
        'fetch_rates' => env('SHOP_FETCH_EXCHANGE_RATES', true),
        'detect_from_ip' => env('SHOP_DETECT_CURRENCY_FROM_IP', true),
        'rates_url' => env('SHOP_EXCHANGE_RATES_URL', 'https://open.er-api.com/v6/latest/USD'),
        'rates_ttl' => env('SHOP_EXCHANGE_RATES_TTL', 43200),
        'geo_url' => env('SHOP_GEO_URL', 'https://ipwho.is/{ip}'),
        'symbols' => [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'NZD' => 'NZ$',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            'PKR' => '₨',
            'BDT' => '৳',
            'SGD' => 'S$',
            'MYR' => 'RM',
            'PHP' => '₱',
            'ZAR' => 'R',
            'NGN' => '₦',
            'EGP' => 'E£',
            'MXN' => 'MX$',
            'BRL' => 'R$',
            'CHF' => 'CHF ',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'TRY' => '₺',
            'HKD' => 'HK$',
            'CNY' => '¥',
            'QAR' => 'QR ',
            'KWD' => 'KD ',
            'BHD' => 'BD ',
            'OMR' => 'OMR ',
            'LKR' => 'Rs',
            'THB' => '฿',
            'IDR' => 'Rp',
        ],
        'supported' => [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'NZD' => 'New Zealand Dollar',
            'INR' => 'Indian Rupee',
            'AED' => 'UAE Dirham',
            'SAR' => 'Saudi Riyal',
            'PKR' => 'Pakistani Rupee',
            'BDT' => 'Bangladeshi Taka',
            'SGD' => 'Singapore Dollar',
            'MYR' => 'Malaysian Ringgit',
            'PHP' => 'Philippine Peso',
            'ZAR' => 'South African Rand',
            'NGN' => 'Nigerian Naira',
            'EGP' => 'Egyptian Pound',
            'MXN' => 'Mexican Peso',
            'BRL' => 'Brazilian Real',
            'CHF' => 'Swiss Franc',
            'SEK' => 'Swedish Krona',
            'NOK' => 'Norwegian Krone',
            'DKK' => 'Danish Krone',
            'PLN' => 'Polish Zloty',
            'TRY' => 'Turkish Lira',
            'HKD' => 'Hong Kong Dollar',
            'CNY' => 'Chinese Yuan',
            'QAR' => 'Qatari Riyal',
            'KWD' => 'Kuwaiti Dinar',
            'BHD' => 'Bahraini Dinar',
            'OMR' => 'Omani Rial',
            'LKR' => 'Sri Lankan Rupee',
            'THB' => 'Thai Baht',
            'IDR' => 'Indonesian Rupiah',
        ],
        'fallback_rates' => [
            'USD' => 1,
            'EUR' => 0.92,
            'GBP' => 0.79,
            'CAD' => 1.36,
            'AUD' => 1.53,
            'NZD' => 1.66,
            'INR' => 83.5,
            'AED' => 3.6725,
            'SAR' => 3.75,
            'PKR' => 278,
            'BDT' => 110,
            'SGD' => 1.35,
            'MYR' => 4.47,
            'PHP' => 58,
            'ZAR' => 18.2,
            'NGN' => 1600,
            'EGP' => 49,
            'MXN' => 17.1,
            'BRL' => 5.1,
            'CHF' => 0.89,
            'SEK' => 10.5,
            'NOK' => 10.7,
            'DKK' => 6.9,
            'PLN' => 3.95,
            'TRY' => 32.5,
            'HKD' => 7.8,
            'CNY' => 7.25,
            'QAR' => 3.64,
            'KWD' => 0.31,
            'BHD' => 0.376,
            'OMR' => 0.385,
            'LKR' => 300,
            'THB' => 36.5,
            'IDR' => 16200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    |
    | Guest carts are tracked with a signed cookie holding an opaque token. The
    | cart is claimed by the customer when they authenticate.
    |
    */

    'cart' => [
        'cookie' => 'cart_token',
        'lifetime_days' => 30,
        'max_quantity_per_line' => 99,
    ],

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    'orders' => [
        'number_prefix' => env('SHOP_ORDER_PREFIX', 'YY'),
        'number_start' => 1001,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    'inventory' => [
        'low_stock_threshold' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */

    'catalog' => [
        'products_per_page' => 12,
        'admin_per_page' => 20,
        'max_options_per_product' => 3,
        'image_disk' => env('SHOP_IMAGE_DISK', 'public'),
        'home_featured_slugs' => [
            'prawns-balchao',
            'brinjal-pickle',
            'bombil-pickle',
            'tendli-pickle',
            'yumyum-four',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Storefront reads are cached for this many seconds. The configured cache
    | store may not support tagging, so invalidation uses versioned keys via
    | App\Support\CacheKeys rather than tags.
    |
    */

    'cache' => [
        'ttl' => env('SHOP_CACHE_TTL', 900),
        'enabled' => env('SHOP_CACHE_ENABLED', true),
    ],

];

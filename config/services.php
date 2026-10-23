<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'naptudong' => [
        'partner_id' => env('NAPTUDONG_PARTNER_ID'),
        'partner_key' => env('NAPTUDONG_PARTNER_KEY'),
        'url' => env('NAPTUDONG_API_URL'),
        'callback_url' => env('NAPTUDONG_CALLBACK_URL'),
        'timeout' => (int) env('NAPTUDONG_TIMEOUT', 15),
        'providers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('NAPTUDONG_PROVIDERS', 'VIETTEL,VINAPHONE,MOBIFONE'))
        ))),
        'denominations' => array_map(
            'intval',
            array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('NAPTUDONG_DENOMINATIONS', '10000,20000,30000,50000,100000,200000,300000,500000,1000000'))
            )))
        ),
    ],

];

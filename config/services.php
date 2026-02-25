<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

     /*
    |--------------------------------------------------------------------------
    | NLP / CBR Service (Flask)
    |--------------------------------------------------------------------------
    */
    'nlp' => [
        'url'          => env('NLP_SERVICE_URL', 'http://127.0.0.1:5000'),
        'internal_key' => env('NLP_SERVICE_KEY', ''),
        'timeout'      => env('NLP_TIMEOUT', 10),
    ],

    'sso' => [
        'url' => env('SSO_URL','https://hub.jtv.co.id'),
        'app_id' => env('APP_ID'),
    ],



    /*
    |--------------------------------------------------------------------------
    | Internal / Custom Services
    |--------------------------------------------------------------------------
    */


];

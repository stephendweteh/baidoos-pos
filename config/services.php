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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'arkesel' => [
        'api_key'   => env('ARKESEL_API_KEY', ''),
        'sender_id' => env('ARKESEL_SENDER_ID', 'BaidoosPOS'),
    ],

    'mtn_momo' => [
        'base_url' => env('MTN_MOMO_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY', ''),
        'api_user' => env('MTN_MOMO_API_USER', ''),
        'api_key' => env('MTN_MOMO_API_KEY', ''),
        'target_environment' => env('MTN_MOMO_TARGET_ENVIRONMENT', 'sandbox'),
        'currency' => env('MTN_MOMO_CURRENCY', 'GHS'),
        'merchant_name' => env('MTN_MOMO_MERCHANT_NAME', 'DAAB C26 ENTERPRISE'),
        'merchant_id' => env('MTN_MOMO_MERCHANT_ID', '047100'),
        'merchant_number' => env('MTN_MOMO_MERCHANT_NUMBER', '0557115748'),
        'callback_url' => env('MTN_MOMO_CALLBACK_URL', ''),
    ],

];

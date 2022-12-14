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

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'api_key' => env('TWILIO_API_KEY'),
        'api_secret' => env('TWILIO_API_SECRET'),
        'twiml_app_sid' => env('TWILIO_TWIML_APP_SID'),
        'phone_number_sid' => env('TWILIO_PHONE_NUMBER_SID'),
        'workspace_sid' => env('TWILIO_TASK_ROUTER_WORKSPACE_SID'),
        'workflow_sid' => env('TWILIO_TASK_ROUTER_WORKFLOW_SID'),
        'phone_number' => env('TWILIO_PHONE_NUMBER'),
    ],

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Kuartal ID (id.kuartal.id / repo kuartal-id/kuartal-login): ONE
    // confidential OAuth client "Kuartal Impact" (application row
    // kuartalimpact-web) with two registered redirect URIs, one per host this
    // app serves. KuartalIdClient::redirectUri() picks the one for the host
    // the browser is on (sessions are per host, so each host signs in
    // separately). Same PKCE + id_token flow as careers.kuartal.id.
    'kuartal_id' => [
        'base_url' => env('KUARTAL_ID_BASE_URL', 'https://id.kuartal.id'),
        'client_id' => env('KUARTAL_ID_CLIENT_ID'),
        'client_secret' => env('KUARTAL_ID_CLIENT_SECRET'),
        'redirect' => env('KUARTAL_ID_REDIRECT_URI', rtrim((string) env('APP_URL', 'http://localhost:8000'), '/').'/auth/kuartal-id/callback'),
        'issuer' => env('KUARTAL_ID_ISSUER', env('KUARTAL_ID_BASE_URL', 'https://id.kuartal.id')),
        'scopes' => env('KUARTAL_ID_SCOPES', 'openid profile email entitlements'),
        'entitlements_refresh_minutes' => (int) env('KUARTAL_ID_ENTITLEMENTS_REFRESH_MINUTES', 15),
    ],

];

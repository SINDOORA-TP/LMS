<?php

/**
 * Zoom API Configuration
 *
 * Server-to-Server OAuth credentials for Zoom meeting management.
 * Create your app at: https://marketplace.zoom.us/
 *
 * Required Zoom scopes: meeting:write:admin, meeting:read:admin
 */

return [
    'account_id'    => env('ZOOM_ACCOUNT_ID', ''),
    'client_id'     => env('ZOOM_CLIENT_ID', ''),
    'client_secret' => env('ZOOM_CLIENT_SECRET', ''),
    'base_url'      => 'https://api.zoom.us/v2',
    'oauth_url'     => 'https://zoom.us/oauth/token',
];

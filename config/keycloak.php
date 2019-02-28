<?php

return [
    'model' => env('KEYCLOAK_USER_MODEL', 'colq2\Keycloak\KeycloakUser'),
    'client_id' => env('KEYCLOAK_CLIENT_ID'),
    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
    'redirect' => env('KEYCLOAK_REDIRECT'),
    'realm' => env('KEYCLOAK_REALM'),
    'base_url' => env('KEYCLOAK_BASE_URL'),
];
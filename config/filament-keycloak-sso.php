<?php

return [

    /**
     * Keycloak base url
     */
    'embedded_auth' => env('KEYCLOAK_EMBEDDED_AUTH', true),

    /**
     * Keycloak base url
     */
    'keycloak_base_url' => env('KEYCLOAK_BASE_URL', 'http://localhost:8080'),

    /**
     * Keycloak base url
     */
    'keycloak_realm' => env('KEYCLOAK_REALM', 'realm'),

    /**
     * Keycloak base url
     */
    'keycloak_client_id' => env('KEYCLOAK_CLIENT_ID', 'client_id'),

    /**
     * Keycloak base url
     */
    'keycloak_client_secret' => env('KEYCLOAK_CLIENT_SECRET', 'client_secret'),

    /**
     * Profile page configurations
     */
    'profile' => [

        /**
         * Subheading CSS styles
         */
        'subheading-styles' => 'background-color: rgba(255, 0, 0, .7);
                                color: #fff;
                                padding: 7px 10px;
                                font-size: .8rem;
                                border-radius: 7px;
                                box-shadow: 1px 1px 5px rgba(119, 119, 119, .5);'

    ],

];

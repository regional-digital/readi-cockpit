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
     * Keycloak roles level, can be:
     * - "realm" the plugin search the role inside the roles assigned in the realm level
     * - "client" the plugin search the role inside the roles assigned in the client level
     * - "both" the plugin search the role inside the roles assigned in both the realm and the client level
     */
    'roles_level' => env('KEYCLOAK_ROLES_LEVEL', 'realm'),

    /**
     * Keycloak resource client
     * If the "roles_level" is defined to "client" or "both" this parameter is user
     * to define the client for the 'resource_access' to get roles
     *
     * by default this value equals to keycloak client id
     */
    'keycloak_resource_client' => env('KEYCLOAK_RESOURCE_CLIENT', env('KEYCLOAK_CLIENT_ID')),

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

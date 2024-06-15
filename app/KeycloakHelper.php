<?php
namespace App;
use GuzzleHttp\Client;

class KeycloakHelper {

    private function connectToKeycloak()
    {
        if(!isset($this->client)) {
            $this->client = new Client();
            $res = $this->client->request('POST', env('KEYCLOAK_BASE_URL').'/realms/'.env('KEYCLOAK_REALM').'/protocol/openid-connect/token', [
                'form_params' => [
                    'client_id' => 'admin-cli'
                    , 'username' => env('KEYCLOAK_API_USER')
                    , 'password' => env('KEYCLOAK_API_PASSWORD')
                    , 'grant_type' => 'password'
                    , 'scope' => 'openid'
                ]
            ]);
            $access_token = json_decode($res->getBody())->access_token;
            $this->headers = ['Authorization' => "bearer {$access_token}"];
        }
    }

    public function get_keycloakgroups($parentgroup = false) {
        $this->connectToKeycloak();
        if(!$parentgroup) {
            $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/groups', ['headers' => $this->headers]);
        }
        else {
            $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/groups/'.$parentgroup.'/children', ['headers' => $this->headers]);
        }
        $groups = json_decode($res->getBody());
        $newgroups = [];
        foreach($groups as $group) {
            $newgroups = array_merge($newgroups, [$group->id => $group->path]);
            $newgroups = array_merge($newgroups, $this->get_keycloakgroups($group->id));
        }
        return $newgroups;
    }

    public static function get_keycloakgroupselectoptions() {
        $KeycloakHelper = new KeycloakHelper();
        $groups = $KeycloakHelper->get_keycloakgroups();
        return $groups;
    }

}

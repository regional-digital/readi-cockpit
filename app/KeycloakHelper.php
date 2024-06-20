<?php
namespace App;
use GuzzleHttp\Client;
use App\Models\Groupmember;
use App\Models\Group;

class KeycloakHelper {

    private Client $client;
    private array $headers;

    private function connect()
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

    private function get_groups($parentgroup = false) {
        $this->connect();
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
            $newgroups = array_merge($newgroups, $this->get_groups($group->id));
        }
        return $newgroups;
    }

    public static function get_groupselectoptions() {
        $KeycloakHelper = new KeycloakHelper();
        $groups = $KeycloakHelper->get_groups();
        return $groups;
    }

    public function get_groupmembers(Group $group)
    {
        $this->connect();
        $kc_group = $group->keycloakgroup;

        $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL')."/admin/realms/".env('KEYCLOAK_REALM')."/groups/$kc_group/members", ['headers' => $this->headers]);
        $kc_groupmembers = json_decode($res->getBody());
        $groupmembers = array();
        foreach($kc_groupmembers as $kc_groupmember) {
            array_push($groupmembers, $kc_groupmember->email);
        }
        return $groupmembers;
    }

    private function get_useridbymail($email) {
        $this->connect();
        $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/users?email='.$email, ['headers' => $this->headers]);
        $kc_users = json_decode($res->getBody());
        $foundKcUser = false;
        foreach($kc_users as $kc_user) {
            if($kc_user->email == $email) {
                $foundKcUser = true;
                $kc_user_id = $kc_user->id;
            }
        }
        if(!$foundKcUser) return $foundKcUser;
        else return $kc_user_id;
    }

    public function update_membership(Groupmember $groupmember) {
        $this->connect();

        $group = $groupmember->group;
        $kc_groupid = $group->keycloakgroup;
        $email = $groupmember->email;

        $kc_user_id = $this->get_useridbymail($email);
        if($kc_user_id === false) return false;
        $groupmembers = $this->get_groupmembers($group);

        if(!in_array($email, $groupmembers) && $groupmember->tobeinkeycloak) {
            $this->client->request('PUT', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/users/'.$kc_user_id.'/groups/'.$kc_groupid, ['headers' => $this->headers]);
        }
        elseif (in_array($email, $groupmembers) && !$groupmember->tobeinkeycloak) {
            $this->client->delete(env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/users/'.$kc_user_id.'/groups/'.$kc_groupid, ['headers' => $this->headers]);
        }
        else {
            return false;
        }
        return true;
    }

    public function user_exists($email) {
        $this->connect();
        $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/users?email='.$email, ['headers' => $this->headers]);
        $kc_users = json_decode($res->getBody());
        $foundKcUser = false;
        foreach($kc_users as $kc_user) {
            if($kc_user->email == $email) {
                $foundKcUser = true;
            }
        }
        return $foundKcUser;
    }




}

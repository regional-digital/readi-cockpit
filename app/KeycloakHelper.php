<?php
namespace App;
use GuzzleHttp\Client;
use App\Models\Groupmember;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class KeycloakHelper {

    private Client $client;
    private array $headers;
    private string $access_token;

    function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        if(!isset($this->client)) {
            $this->client = new Client();
        }
        if(!isset($this->access_token)) {
            $token_age = time() - session('keycloakhelpder_access_token_age', 0);
            if(session()->missing('keycloakhelpder_access_token') || $token_age > 30) {
                $res = $this->client->request('POST', env('KEYCLOAK_BASE_URL').'/realms/'.env('KEYCLOAK_REALM').'/protocol/openid-connect/token', [
                    'form_params' => [
                        'client_id' => 'admin-cli'
                        , 'username' => env('KEYCLOAK_API_USER')
                        , 'password' => env('KEYCLOAK_API_PASSWORD')
                        , 'grant_type' => 'password'
                        , 'scope' => 'openid'
                    ]
                ]);
                $this->access_token = json_decode($res->getBody())->access_token;
                session(['keycloakhelpder_access_token' => $this->access_token]);
                session(['keycloakhelpder_access_token_age' => time()]);
            } else {
                $this->access_token = session('keycloakhelpder_access_token');
            }
        }
        if(!isset($this->headers)) {
            $this->headers = ['Authorization' => "bearer {$this->access_token}"];
        }
    }

    public function get_groups($parentgroup = false) {
        if(!$parentgroup) {
            $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/groups', ['headers' => $this->headers]);
        }
        else {
            $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/groups/'.$parentgroup.'/children?max=200', ['headers' => $this->headers]);
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
        if(Cache::get("get_groupselectoptions")) {
            return json_decode(Cache::get("get_groupselectoptions"), true);
        }
        $KeycloakHelper = new KeycloakHelper();
        $groups = $KeycloakHelper->get_groups();
        Cache::add("get_groupselectoptions", json_encode($groups), now()->addSeconds((int)env('CACHE_TIME', 600)));
        return $groups;
    }

    public function get_groupmembers(Group $group)
    {
        return $this->get_keycloakgroupmembers($group->keycloakgroup);
    }

    public function get_groupadminmembers(Group $group)
    {
        return $this->get_keycloakgroupmembers($group->keycloakadmingroup);
    }

    public function get_keycloakgroupmembers(String $kc_group)
    {
        if(Cache::get("get_keycloakgroupmembers".md5($kc_group))) {
            return json_decode(Cache::get("get_keycloakgroupmembers".md5($kc_group)));
        }
        $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL')."/admin/realms/".env('KEYCLOAK_REALM')."/groups/$kc_group/members", ['headers' => $this->headers]);
        $kc_groupmembers = json_decode($res->getBody());
        $groupmembers = array();
        foreach($kc_groupmembers as $kc_groupmember) {
            array_push($groupmembers, $kc_groupmember->email);
        }
        Cache::add("is_Groupadmin".md5($kc_group), json_encode($groupmembers), now()->addSeconds((int)env('CACHE_TIME', 600)));
        return $groupmembers;
    }


    public function is_groupadmin(Group $group, String $email): bool
    {
        if(Cache::get("is_Groupadmin".md5($group.$email))) {
            return Cache::get("is_Groupadmin".md5($group.$email));
        }
        $kc_admingroup = $group->keycloakadmingroup;
        $kc_user = $this->get_useridbymail($email);
        $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL')."/admin/realms/".env('KEYCLOAK_REALM')."/users/$kc_user/groups", ['headers' => $this->headers]);
        $kc_groups = json_decode($res->getBody());
        foreach($kc_groups as $kc_group) {
            Cache::add("is_Groupadmin".md5($group.$email), true, now()->addSeconds((int)env('CACHE_TIME', 600)));
            if($kc_group->id == $kc_admingroup) return true;
        }
        Cache::add("is_Groupadmin".md5($group.$email), false, now()->addSeconds((int)env('CACHE_TIME', 600)));
        return false;
    }

    private function get_useridbymail($email) {
        if(Cache::get("get_useridbymail".md5($email))) {
            return Cache::get("get_useridbymail".md5($email));
        }
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
        else {
            Cache::add("get_useridbymail".md5($email), $kc_user_id, now()->addSeconds((int)env('CACHE_TIME', 600)));
            return $kc_user_id;
        }
    }

    public function update_membership(Groupmember $groupmember) {
        if(Cache::get("update_membership".md5($groupmember))) {
            return Cache::get("update_membership".md5($groupmember));
        }
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
            Cache::add("update_membership".md5($email), false, now()->addSeconds((int)env('CACHE_TIME', 600)));
            return false;
        }
        Cache::add("update_membership".md5($email), true, now()->addSeconds((int)env('CACHE_TIME', 600)));
        return true;
    }

    public function user_exists($email) {
        if(Cache::get("user_exists".md5($email))) {
            return Cache::get("user_exists".md5($email));
        }
        $res = $this->client->request('GET', env('KEYCLOAK_BASE_URL').'/admin/realms/'.env('KEYCLOAK_REALM').'/users?email='.$email, ['headers' => $this->headers]);
        $kc_users = json_decode($res->getBody());
        $foundKcUser = false;
        foreach($kc_users as $kc_user) {
            if($kc_user->email == $email) {
                Cache::add("user_exists".md5($email), true, now()->addSeconds((int)env('CACHE_TIME', 600)));
                $foundKcUser = true;
            }
        }
        Cache::add("user_exists".md5($email), false, now()->addSeconds((int)env('CACHE_TIME', 600)));
        return $foundKcUser;
    }







}

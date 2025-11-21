<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keycloakuser extends Model
{
    protected $fillable = [
        "keycloak_id",
        "username",
        "email",
        "email_original",
        "lastsynctime",
    ];

}

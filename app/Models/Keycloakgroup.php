<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keycloakgroup extends Model
{
    protected $fillable = [
        "name", 
        "path", 
        "groupId", 
        "parentId", 
        "parent_id", 
        "lastsynctime", 
    ];
}

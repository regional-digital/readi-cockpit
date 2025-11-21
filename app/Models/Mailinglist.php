<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mailinglist extends Model
{
    protected $fillable = [
        "name",
        "url",
        "password",
        "lastsynctime",
    ];

    protected $hidden = [
        'password'
    ];

}

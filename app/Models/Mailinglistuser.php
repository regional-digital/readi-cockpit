<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mailinglistuser extends Model
{
    protected $fillable = [
        "email",
        "email_original",
        "mailinglist_id",
    ];
}

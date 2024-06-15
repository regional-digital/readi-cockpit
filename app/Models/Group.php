<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        "name"
        , "description"
        , "moderated"
        , "has_mailinglist"
        , "has_keycloakgroup"
        , "mailinglisturl"
        , "keycloakgroup"
        , "keycloakadmingroup"
        , "mailinglistpassword"
    ];

    protected $hidden = [
        'mailinglistpassword'
    ];

    /**
     * Get all of the comments for the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function groupmembers(): HasMany
    {
        return $this->hasMany(Groupmembers::class);
    }

}

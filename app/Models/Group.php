<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        , "keycloakadminrole"
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
        return $this->hasMany(Groupmember::class);
    }

}

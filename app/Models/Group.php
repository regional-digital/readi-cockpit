<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\GroupObserver;
use Illuminate\Support\Facades\Auth;

#[ObservedBy([GroupObserver::class])]
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
        , "mailinglistpassword"
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

    public function is_groupmember(string $email): bool
    {
        $groupmembers = $this->groupmembers;
        $is_groupmember = false;
        foreach($groupmembers as $groupmember) {
            if(strtolower($groupmember->email) == $email) $is_groupmember = true;
        }
        return $is_groupmember;
    }

    public function joinGroup() {
        $email = Auth::user()->email;
        if(!$this->is_groupmember($email)) {
            $groupmember = new Groupmember(["email" => $email]);
            $this->groupmembers()->save($groupmember);
        }
    }

    public function leaveGroup() {
        $email = Auth::user()->email;
        if($this->is_groupmember($email)) {
            Groupmember::where("email", $email)->first()->delete();
        }
    }

}

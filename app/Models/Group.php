<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\GroupObserver;
use Illuminate\Support\Facades\Auth;
use App\KeycloakHelper;
use App\MailmanHelper;

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
            if($this->moderated) $waitingforjoin = true;
            else $waitingforjoin = false;
            $groupmember = new Groupmember(["email" => $email, 'waitingforjoin' => $waitingforjoin]);
            $this->groupmembers()->save($groupmember);
        }
    }

    public function leaveGroup() {
        $email = Auth::user()->email;
        if($this->is_groupmember($email)) {
            Groupmember::where("email", $email)->first()->delete();
        }
    }

    public function updateGroupMembers() {
        $keycloakHelper = new KeycloakHelper();
        $kc_groupmembers = $keycloakHelper->get_groupmembers($this);

        $mailmanhelper = new MailmanHelper();
        $mailman_groupmembers = $mailmanhelper->get_mailmanmembers($this);

        $groupmembers = $this->groupmembers()->pluck("email")->all();

        foreach($this->groupmembers as $groupmember) {
            $groupmemberChanged = false;
            if(!in_array($groupmember->email, $kc_groupmembers) && $groupmember->tobeinkeycloak == true) {
                $groupmember->tobeinkeycloak = false;
                $groupmemberChanged = true;
            }
            if(in_array($groupmember->email, $kc_groupmembers) && $groupmember->tobeinkeycloak == false) {
                $groupmember->tobeinkeycloak = true;
                $groupmemberChanged = true;
            }

            if(!in_array($groupmember->email, $mailman_groupmembers) && $groupmember->tobeinmailinglist == true) {
                $groupmember->tobeinmailinglist = false;
                $groupmemberChanged = true;
            }
            if(in_array($groupmember->email, $mailman_groupmembers) && $groupmember->tobeinmailinglist == false) {
                $groupmember->tobeinmailinglist = true;
                $groupmemberChanged = true;
            }

            if($groupmemberChanged) {
                $groupmember->save();
            }
        }

        $allAppMembers = array_merge($kc_groupmembers, $mailman_groupmembers);
        $missingMembers = array_diff($allAppMembers, $groupmembers);
        foreach($missingMembers as $missingMember) {
            if(in_array($missingMember, $kc_groupmembers)) $tobeinkeycloak = true;
            else $tobeinkeycloak = false;
            if(in_array($missingMember, $mailman_groupmembers)) $tobeinmailman = true;
            else $tobeinmailman = false;
            $groupmember = new Groupmember([
                "email" => $missingMember
                , "tobeinkeycloak" => $tobeinkeycloak
                , 'tobeinmailman' => $tobeinmailman
            ]);
            $this->groupmembers()->save($groupmember);
        }
    }

}

<?php

namespace App\Observers;

use App\Models\Groupmember;
use App\KeycloakHelper;
use App\MailmanHelper;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserWaitingForJoin;

class GroupmemberObserver
{
    /**
     * Handle the Groupmember "updated" event.
     */
    public function update(Groupmember $groupmember): void
    {
        //if user is waiting for join, do nothing
        //if user was joined, update state in keycloak and mailman

        if ($groupmember->tobeinkeycloak != $groupmember->getOriginal('tobeinkeycloak')) {
            $KeycloakHelper = new KeycloakHelper();
            $KeycloakHelper->update_membership($groupmember);
        }
        if ($groupmember->tobeinmailman != $groupmember->getOriginal('tobeinmailman')) {
            $MailmanHelper = new MailmanHelper();
            $MailmanHelper->update_membership($groupmember);
        }

    }

    public function created(Groupmember $groupmember) {
        if($groupmember->group->moderated && $groupmember->waitingforjoin) {
            $KeycloakHelper = new KeycloakHelper();
            $groupadmins = $KeycloakHelper->get_groupadminmembers($groupmember->group);

            Mail::to($groupadmins)->send(new UserWaitingForJoin($groupmember));

        }
    }
}

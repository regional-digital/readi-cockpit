<?php

namespace App\Observers;

use App\Models\Groupmember;
use App\KeycloakHelper;
use App\MailmanHelper;

class GroupmemberObserver
{
    /**
     * Handle the Groupmember "updated" event.
     */
    public function updated(Groupmember $groupmember): void
    {
        $KeycloakHelper = new KeycloakHelper();
        $KeycloakHelper->update_membership($groupmember);

        $MailmanHelper = new MailmanHelper();
        $MailmanHelper->update_membership($groupmember);
    }

}

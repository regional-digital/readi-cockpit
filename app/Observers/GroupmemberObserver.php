<?php

namespace App\Observers;

use App\Models\Groupmember;
use App\KeycloakHelper;

class GroupmemberObserver
{
    /**
     * Handle the Groupmember "updated" event.
     */
    public function updated(Groupmember $groupmember): void
    {
        $KeycloakHelper = new KeycloakHelper();
        $KeycloakHelper::update_membership($groupmember);
    }

}

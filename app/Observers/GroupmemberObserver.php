<?php

namespace App\Observers;

use App\Models\Groupmember;

class GroupmemberObserver
{
    /**
     * Handle the Groupmember "created" event.
     */
    public function created(Groupmember $groupmember): void
    {
        //
    }

    /**
     * Handle the Groupmember "updated" event.
     */
    public function updated(Groupmember $groupmember): void
    {
        //
    }

    /**
     * Handle the Groupmember "deleted" event.
     */
    public function deleted(Groupmember $groupmember): void
    {
        //
    }

    /**
     * Handle the Groupmember "restored" event.
     */
    public function restored(Groupmember $groupmember): void
    {
        //
    }

    /**
     * Handle the Groupmember "force deleted" event.
     */
    public function forceDeleted(Groupmember $groupmember): void
    {
        //
    }
}

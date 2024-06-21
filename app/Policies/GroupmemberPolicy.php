<?php

namespace App\Policies;

use App\KeycloakHelper;
use App\Models\Groupmember;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GroupmemberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Groupmember $groupmember): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Groupmember $groupmember): bool
    {
        $keycloakhelper = new KeycloakHelper();
        if(!in_array("Administrator", $user->roles()) && !$keycloakhelper->is_groupadmin($groupmember->group, $user->email) && $user->email !== $groupmember->email) {
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Groupmember $groupmember): bool
    {
        $keycloakhelper = new KeycloakHelper();
        if($user->email == $groupmember->email || in_array("Administrator", $user->roles()) || $keycloakhelper->is_groupadmin($groupmember->group, $user->email)) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Groupmember $groupmember): bool
    {
        $keycloakhelper = new KeycloakHelper();
        if(in_array("Administrator", $user->roles()) || $keycloakhelper->is_groupadmin($groupmember->group, $user()->email)) return true;
        else return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Groupmember $groupmember): bool
    {
        $keycloakhelper = new KeycloakHelper();
        if(in_array("Administrator", $user->roles()) || $keycloakhelper->is_groupadmin($groupmember->group, $user()->email)) return true;
        else return false;
    }
}

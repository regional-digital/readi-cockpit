<?php
namespace App;
use App\Models\Groupmember;
use App\Models\Group;
use splattner\MailmanAPI\mailmanAPI;

class MailmanHelper {
    public function get_mailmanmembers(Group $group): array
    {
        $mailmanapi = new MailmanAPI($group->mailinglisturl, $group->mailinglistpassword);
        $mailmanmembers = $mailmanapi->getMemberlist();
        return $mailmanmembers;
    }

    private function remove_mailmanmember(Groupmember $groupmember): bool
    {
        $mailmanapi = new MailmanAPI($groupmember->group->mailinglisturl, $groupmember->group->mailinglistpassword);
        $mailmanapi->removeMembers([$groupmember->email]);
        return true;
    }

    private function add_mailmanmember(Groupmember $groupmember): bool
    {
        $mailmanapi = new MailmanAPI($groupmember->group->mailinglisturl, $groupmember->group->mailinglistpassword);
        $mailmanapi->addMembers([$groupmember->email]);
        return true;
    }

    public function update_membership(Groupmember $groupmember) {
        $members = $this->get_mailmanmembers($groupmember->group);
        if(!in_array($groupmember->email, $members) && $groupmember->tobeinmailinglist) {
            $this->add_mailmanmember($groupmember);
        }
        else
        {
            $this->remove_mailmanmember($groupmember);
        }

    }
}

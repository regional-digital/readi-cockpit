<?php
namespace App;
use App\Models\Groupmember;
use App\Models\Group;
use splattner\MailmanAPI\mailmanAPI;
use \Exception;
use Filament\Notifications\Notification; 

class MailmanHelper {
    public function get_mailmanmembers(Group $group): array
    {
        try {
            $mailmanapi = new MailmanAPI($group->mailinglisturl, $group->mailinglistpassword);
        } catch (Exception $e) {
            Notification::make()
                ->title('Gruppe konnte auf Mailman nicht abgefragt werden. Deaktiviere Mailman-Feature.')
                ->danger()
                ->send();
                $group->has_mailinglist = false;
                $group->save();
                return [];
        }
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

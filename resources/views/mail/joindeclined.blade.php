<x-mail::message>
# Beitritt zur Gruppe genehmigt

Hallo {{ $groupmember->group->name }}.
Ein Administrator oder ein Gruppenadministrator hat deinen Beitritt zur Gruppe {{ $groupmember->group->name }} abgelehnt.

<x-mail::button :url="$url" color="success">
    Gruppe aufrufen
</x-mail::button>

</x-mail::message>

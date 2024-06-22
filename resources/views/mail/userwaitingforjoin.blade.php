<x-mail::message>
# Benutzer wartet auf Beitritt

{{ $groupmember->group->name }} wartet darauf, der Gruppe {{ $groupmember->group->name }} beizutreten.

<x-mail::button :url="$url" color="success">
    Gruppe aufrufen
</x-mail::button>

</x-mail::message>

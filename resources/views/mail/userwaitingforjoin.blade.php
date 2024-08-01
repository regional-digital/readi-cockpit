<x-mail::message>
# Benutzer wartet auf Beitritt

{{ $groupmember->email }} wartet darauf, der Gruppe {{ $groupmember->group->name }} beizutreten.

<x-mail::button :url="$url" color="success">
    Gruppe aufrufen
</x-mail::button>

</x-mail::message>

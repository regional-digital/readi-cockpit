<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\KeycloakHelper;
use App\Mail\UserWaitingForJoin;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Group;
use App\Models\Groupmember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component as Livewire;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class ViewGroup extends ViewRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Gruppe beitreten')
                ->icon('heroicon-m-plus-circle')
                ->requiresConfirmation()
                ->action(function (Group $group, Livewire $livewire) {
                    $group->joinGroup();
                    if($group->moderated) {
                        $KeycloakHelper = new KeycloakHelper();
                        $groupadmins = $KeycloakHelper->get_groupadminmembers($group);
                        $groupmember = Groupmember::where('email', Auth::user()->email)->first();
                        Mail::to($groupadmins)->send(new UserWaitingForJoin($groupmember));
                    }
                    $livewire->dispatch('refreshRelations');
                })
                ->visible(function(Group $group) {
                    return !($group->is_groupmember(Auth::user()->email));
                })
                ->modalHeading('Gruppe beitreten')
                ->modalDescription("Der Projektgruppe beitreten?")
                ->modalSubmitActionLabel('Ja')
                ->modalCancelActionLabel('Nein')
                ->slideOver(),
            Actions\Action::make('Gruppe verlassen')
                ->icon('heroicon-m-minus-circle')
                ->requiresConfirmation()
                ->action(function (Group $group, Livewire $livewire) {
                    $group->leaveGroup();
                    $livewire->dispatch('refreshRelations');
                })
                ->visible(function(Group $group) {
                    return ($group->is_groupmember(Auth::user()->email));
                })
                ->modalHeading('Gruppe Verlasse')
                ->modalDescription("Die Projektgruppe verlassen?")
                ->modalSubmitActionLabel('Ja')
                ->modalCancelActionLabel('Nein')
                ->slideOver(),
            Actions\EditAction::make()->label("Bearbeiten"),
        ];
    }
}

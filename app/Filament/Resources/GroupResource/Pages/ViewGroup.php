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
                        if($group->keycloakadmingroup != null) {
                            $KeycloakHelper = new KeycloakHelper();
                            $groupadmins = $KeycloakHelper->get_groupadminmembers($group);
                            if(is_array($groupadmins) && count($groupadmins) > 0) {
                                $groupmember = Groupmember::where('email', Auth::user()->email)->where('group_id', $group->id)->first();
                                Mail::to($groupadmins)->send(new UserWaitingForJoin($groupmember));
                            }
                        }
                    }
                    $livewire->dispatch('refreshRelations');
                })
                ->visible(function(Group $group) {
                    return !($group->is_groupmember(Auth::user()->email));
                })
                ->modalDescription("Der Gruppe beitreten?")
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
                ->modalHeading('Gruppe verlassen')
                ->modalDescription("Die Gruppe verlassen?")
                ->modalSubmitActionLabel('Ja')
                ->modalCancelActionLabel('Nein')
                ->slideOver(),
            Actions\Action::make('Email')
                ->icon('heroicon-m-envelope-open')
                ->button()
                ->url(function(Group $group) {
                    $mailto = [];
                    $groupmembers = $group->groupmembers;
                    foreach($groupmembers as $groupmember) {
                        if($groupmember->waitingforjoin) continue;
                        array_push($mailto, $groupmember->email);
                    }
                    $url = "mailto:".implode(", ", $mailto);
                    return $url;
                }),
            Actions\Action::make("Infoseite")
                ->url(function(Group $group) {
                    return "https://".$group->url;
                })
                ->openUrlInNewTab()
                ->visible(function(Group $group) {
                    if(isset($group->url) && trim($group->url) != '') {
                        return true;
                    }
                    else {
                        return false;
                    }
                })
                ->icon('heroicon-m-globe-alt')  ,
            Actions\EditAction::make(),
        ];
    }
}

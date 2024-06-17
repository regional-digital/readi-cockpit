<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Livewire\Component as Livewire;

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
                    $livewire->dispatch('refreshRelations');
                })
                ->visible(function(Group $group) {
                    return !($group->is_groupmember(Auth::user()->email));
                }),
            Actions\Action::make('Gruppe verlassen')
                ->icon('heroicon-m-minus-circle')
                ->requiresConfirmation()
                ->action(function (Group $group, Livewire $livewire) {
                    $group->leaveGroup();
                    $livewire->dispatch('refreshRelations');
                })
                ->visible(function(Group $group) {
                    return ($group->is_groupmember(Auth::user()->email));
                }),
            Actions\EditAction::make(),
        ];
    }
}

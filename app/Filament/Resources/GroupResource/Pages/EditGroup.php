<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label("Anschauen"),
            Actions\DeleteAction::make()
                ->label("Löschen")
                ->modalHeading('Gruppe löschen')
                ->modalDescription("Gruppe wirklich löschen? Das löscht nicht die Mitglieder aus den Anwendungen.")
                ->modalSubmitActionLabel('Ja')
                ->modalCancelActionLabel('Nein')
                ->slideOver(),
            Actions\ForceDeleteAction::make()->label("Endgültig löschen"),
            Actions\RestoreAction::make()->label("Wiederherstellen"),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

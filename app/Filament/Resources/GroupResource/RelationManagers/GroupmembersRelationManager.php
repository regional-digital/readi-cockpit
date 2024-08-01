<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use App\Models\Groupmember;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\KeycloakHelper;
use App\Mail\JoinApproved;
use App\MailmanHelper;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Livewire\Component as Livewire;
use Illuminate\Support\Facades\Mail;
use App\Mail\JoinDeclined;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class GroupmembersRelationManager extends RelationManager
{
    protected static string $relationship = 'Groupmembers';

    protected $listeners = ['refreshRelations' => '$refresh'];

    protected static ?string $title = "Gruppenmitglieder";

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('tobeinkeycloak')
                    ->label("Keycloak"),
                Forms\Components\Toggle::make('tobeinmailinglist')
                    ->label("Mailingliste")
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label("E-Mail")
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('tobeinkeycloak')
                    ->label('Keycloak')
                    ->tooltip(function (Model $record) {
                        if($record->waitingforjoin) return "Deaktiviert, weil der Benutzer noch auf Beitritt wartet";
                        $keycloakhelper = new KeycloakHelper();
                        if(!$keycloakhelper->user_exists($record->email)) {
                            return "Deaktiviert, weil der Benutzer im Keycloak nicht existiert";
                        }
                        else return "";
                    })
                    ->disabled(function (Model $record): bool
                    {
                        if($record->waitingforjoin) return true;
                        $keycloakhelper = new KeycloakHelper();
                        $user = User::where('email', Auth::user()->email)->first();
                        if(!$keycloakhelper->user_exists($record->email)) return true;
                        if(!in_array("Administrator", $user->roles()) && !$keycloakhelper->is_groupadmin($this->getOwnerRecord(), $user->email) && $user->email !== $record->email) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    })
                    ->beforeStateUpdated(function ($record, $state, Group $group) {
                        if($group->has_keycloakgroup && $group->keycloakgroup != null) {
                            $record->tobeinkeycloak = $state;
                            $KeycloakHelper = new KeycloakHelper();
                            $KeycloakHelper->update_membership($record);
                        }
                    })
                    ->hidden(function() {
                        if($this->getOwnerRecord()->has_keycloakgroup && $this->getOwnerRecord()->keycloakgroup != null) return false;
                        else return true;
                    }),
                Tables\Columns\ToggleColumn::make('tobeinmailinglist')
                    ->label('Mailingliste')
                    ->tooltip(function(Model $record) {
                            if($record->waitingforjoin) return "Deaktiviert, weil der Benutzer noch auf Beitritt wartet";
                        }
                    )
                    ->visible(function() {
                        if($this->getOwnerRecord()->has_mailinglist && $this->getOwnerRecord()->mailinglisturl != null && $this->getOwnerRecord()->mailinglistpassword != null) {
                            return false;
                        }
                        else return true;
                    })
                    ->disabled(function(Model $record): bool
                    {
                        if($record->waitingforjoin) return true;
                        $keycloakhelper = new KeycloakHelper();
                        $user = User::where('email', Auth::user()->email)->first();
                        if(!in_array("Administrator", $user->roles()) && !$keycloakhelper->is_groupadmin($this->getOwnerRecord(), $user->email) && $user->email !== $record->email) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    })
                    ->beforeStateUpdated(function ($record, $state) {
                        $record->tobeinmailinglist = $state;
                        $MailmanHelper = new MailmanHelper();
                        $MailmanHelper->update_membership($record);
                    }),
                ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label("Neues Gruppenmitglied")
                    ->before(function (array $data, Tables\Actions\CreateAction $action, RelationManager $livewire) {
                        if ($livewire->ownerRecord->groupmembers()->where("email", $data['email'])->first()) {
                            Notification::make()
                                ->warning()
                                ->title('Gruppenmitglied existiert bereits!')
                                ->body('Gruppenmitglied existiert bereits!')
                                ->persistent()
                                ->send();
                            $action->halt();
                        }
                    })
                    ->after(function (Model $record) {
                        if ($record->tobeinkeycloak) {
                            $KeycloakHelper = new KeycloakHelper();
                            $KeycloakHelper->update_membership($record);
                        }
                        if ($record->tobeinmailinglist) {
                            $MailmanHelper = new MailmanHelper();
                            $MailmanHelper->update_membership($record);
                        }
                    })
                    ->slideOver(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Gruppenmitglied löschen')
                    ->modalDescription("Gruppenmitglied wirklich löschen? Das löscht die Adresse aus allen Anwendungen und kann nicht rückgängig gemacht werden")
                    ->modalSubmitActionLabel('Ja')
                    ->modalCancelActionLabel('Nein')
                    ->slideOver()
                    ->before(function (Model $record) {
                        if ($record->tobeinkeycloak) {
                            $record->tobeinkeycloak = false;
                            $KeycloakHelper = new KeycloakHelper();
                            $KeycloakHelper->update_membership($record);
                        }
                        if ($record->tobeinmailinglist) {
                            $record->tobeinmailinglist = false;
                            $MailmanHelper = new MailmanHelper();
                            $MailmanHelper->update_membership($record);
                        }
                    }),
                Tables\Actions\ForceDeleteAction::make()->label("Endgültig löschen"),
                Tables\Actions\RestoreAction::make()->label("Wiederherstellen"),
                Tables\Actions\Action::make('Genehmigen')
                    ->icon('heroicon-m-face-smile')
                    ->requiresConfirmation()
                    ->label("Genehmigen")
                    ->modalHeading('Gruppenmitgliedschaft genehmigen')
                    ->modalDescription("Gruppenmitgliedschaft genehmigen?")
                    ->modalSubmitActionLabel('Ja')
                    ->modalCancelActionLabel('Nein')
                    ->slideOver()
                    ->action(function (Groupmember $groupmember, Livewire $livewire) {
                        $groupmember->waitingforjoin = false;
                        $groupmember->save();
                        Mail::to($groupmember->email)->send(new JoinApproved($groupmember));
                        $livewire->dispatch('refreshRelations');
                    })->visible(function(Groupmember $groupmember) {
                        $keycloakhelper = new KeycloakHelper();
                        $user = User::where('email', Auth::user()->email)->first();
                        if($groupmember->waitingforjoin && (in_array("Administrator", $user->roles()) || ($this->getOwnerRecord()->moderated && $keycloakhelper->is_groupadmin($this->getOwnerRecord(), $user->email)))) return true;
                        else return false;
                    }),
                Tables\Actions\Action::make('Ablehnen')
                    ->icon('heroicon-m-face-frown')
                    ->requiresConfirmation()
                    ->label("Ablehnen")
                    ->modalHeading('Gruppenmitgliedschaft ablehnen')
                    ->modalDescription("Gruppenmitgliedschaft ablehnen?")
                    ->modalSubmitActionLabel('Ja')
                    ->modalCancelActionLabel('Nein')
                    ->slideOver()
                    ->action(function (Groupmember $groupmember, Livewire $livewire) {
                        $groupmember->delete();
                        Mail::to($groupmember->email)->send(new JoinDeclined($groupmember));
                        $livewire->dispatch('refreshRelations');
                    })->visible(function(Groupmember $groupmember) {
                        $keycloakhelper = new KeycloakHelper();
                        $user = User::where('email', Auth::user()->email)->first();
                        if($groupmember->waitingforjoin && $groupmember->group->moderated && $keycloakhelper->is_groupadmin($this->getOwnerRecord(), $user->email)) return true;
                        return false;
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort("email");
    }
}

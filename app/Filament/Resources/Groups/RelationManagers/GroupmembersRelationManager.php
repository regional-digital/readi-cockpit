<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Models\Groupmember;
use App\Models\Group;
use Filament\Forms;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->required()
                    ->maxLength(255),
                Toggle::make('tobeinkeycloak')
                    ->label("Keycloak"),
                Toggle::make('tobeinmailinglist')
                    ->label("Mailingliste")
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('email')
                    ->label("E-Mail")
                    ->sortable()
                    ->searchable(),
                ToggleColumn::make('tobeinkeycloak')
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
                ToggleColumn::make('tobeinmailinglist')
                    ->label('Mailingliste')
                    ->tooltip(function(Model $record) {
                            if($record->waitingforjoin) return "Deaktiviert, weil der Benutzer noch auf Beitritt wartet";
                        }
                    )
                    ->visible(function() {
                        if(!$this->getOwnerRecord()->has_mailinglist && $this->getOwnerRecord()->mailinglisturl != null && $this->getOwnerRecord()->mailinglistpassword != null) {
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
                TrashedFilter::make()
            ])
            ->headerActions([
                CreateAction::make()
                    ->label("Neues Gruppenmitglied")
                    ->before(function (array $data, CreateAction $action, RelationManager $livewire) {
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
            ->recordActions([
                DeleteAction::make()
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
                ForceDeleteAction::make()->label("Endgültig löschen"),
                RestoreAction::make()->label("Wiederherstellen"),
                \Filament\Actions\Action::make('Genehmigen')
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
                \Filament\Actions\Action::make('Ablehnen')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort("email");
    }
}

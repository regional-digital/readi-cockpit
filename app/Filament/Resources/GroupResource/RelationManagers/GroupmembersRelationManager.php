<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use App\Models\Groupmember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\KeycloakHelper;
use App\MailmanHelper;

class GroupmembersRelationManager extends RelationManager
{
    protected static string $relationship = 'Groupmembers';

    protected $listeners = ['refreshRelations' => '$refresh'];

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
                    ->label("Mailingliste"),
                Forms\Components\Toggle::make('waitingforjoin')
                    ->label("Wartet auf Beitritt"),
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label("E-Mail"),
                Tables\Columns\ToggleColumn::make('tobeinkeycloak')
                    ->label('Keycloak')
                    ->tooltip(function (Model $record) {
                        $keycloakhelper = new KeycloakHelper();
                        if(!$keycloakhelper->user_exists($record->email)) {
                            return "Deaktiviert, weil der Benutzer im Keycloak nicht existiert";
                        }
                        else return "";
                    })
                    ->visible(function() {
                        return $this->getOwnerRecord()->has_keycloakgroup;
                    })
                    ->disabled(function (Model $record): bool
                    {
                        $keycloakhelper = new KeycloakHelper();
                        return !$keycloakhelper->user_exists($record->email);
                    }),
                Tables\Columns\ToggleColumn::make('tobeinmailinglist')
                    ->label('Mailingliste')
                    ->visible(function() {
                        return $this->getOwnerRecord()->has_mailinglist;
                    }),
                Tables\Columns\ToggleColumn::make('waitingforjoin')
                    ->label('wartet auf Beitrit')
                    ->visible(function() {
                        return $this->getOwnerRecord()->moderated;
                    }),
                ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
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
            ]));
    }
}

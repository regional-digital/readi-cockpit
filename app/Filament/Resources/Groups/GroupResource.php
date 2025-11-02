<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Auth;
use App\KeycloakHelper;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component as Livewire;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Gruppe';
    protected static ?string $pluralModelLabel = 'Gruppen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required(),
                    Forms\Components\TextInput::make('description')
                        ->columnSpan(2)
                        ->label("Beschreibung"),
                    Forms\Components\Toggle::make('moderated')
                        ->label("Gruppe ist moderiert"),
                    Forms\Components\Select::make('grouptype')
                        ->label("Gruppentyp")
                        ->options([
                            "Projektgruppe" => "Projektgruppe"
                            , "Fachgruppe" => "Fachgruppe"
                            , "Netzwerktreffen" => "Netzwerktreffen"
                            , "Technische Gruppe" => "Technische Gruppe"
                            , "Organisatorische Gruppe" => "Organisatorische Gruppe"
                        ]),
                    Forms\Components\TextInput::make('url')
                        ->label("URL")
                        ->prefix('https://'),
                    Forms\Components\Toggle::make('has_mailinglist')
                        ->label("Hat eine Mailingliste"),
                    Forms\Components\TextInput::make('mailinglisturl')
                        ->label("Mailinglisten-URL")
                        ->requiredIf('has_mailinglist', true),
                    Forms\Components\TextInput::make('mailinglistpassword')
                        ->label("Mailinglisten-Passwort")
                        ->dehydrated(fn ($state) => filled($state))
                        ->password(),
                    Forms\Components\Toggle::make('has_keycloakgroup')
                        ->label("Hat eine Keycloak-Gruppe"),
                    Forms\Components\Select::make('keycloakgroup')
                        ->options(KeycloakHelper::get_groupselectoptions())
                        ->requiredIf('has_keycloakgroup', true)
                        ->searchable(),
                    Forms\Components\Select::make('keycloakadmingroup')
                        ->options(KeycloakHelper::get_groupselectoptions())
                        ->requiredIf('moderated', true)
                        ->searchable(),
                ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('grouptype')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label("Gruppentyp"),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->label("Beschreibung")
                    ->toggleable(),
                Tables\Columns\IconColumn::make('moderated')
                    ->sortable()
                    ->label("moderiert")
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_mailinglist')
                    ->sortable()
                    ->label("Mailingliste")
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_keycloakgroup')
                    ->label("Keycloak-Gruppe")
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mailinglisturl')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('keycloakgroup')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('keycloakadmingroup')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make("grouptype")
                    ->label("Gruppentyp")
                    ->multiple()
                    ->options([
                        "Projektgruppe" => "Projektgruppe"
                        , "Fachgruppe" => "Fachgruppe"
                        , "Netzwerktreffen" => "Netzwerktreffen"
                        , "Technische Gruppe" => "Technische Gruppe"
                        , "Organisatorische Gruppe" => "Organisatorische Gruppe"
                    ])
                    ->searchable()
                    ->default(["Projektgruppe", "Netzwerktreffen"]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label("Anschauen"),
                Tables\Actions\EditAction::make()->label("Bearbeiten")
                ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label("Löschen"),
                    Tables\Actions\ForceDeleteBulkAction::make()->label("Endgültig löschen"),
                    Tables\Actions\RestoreBulkAction::make()->label("Wiederherstellen"),
                ]),
            ])
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GroupmembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'view' => Pages\ViewGroup::route('/{record}'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

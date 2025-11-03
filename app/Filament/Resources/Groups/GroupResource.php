<?php

namespace App\Filament\Resources\Groups;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\Groups\RelationManagers\GroupmembersRelationManager;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\ViewGroup;
use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers;
use App\Models\Group;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Auth;
use App\KeycloakHelper;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component as Livewire;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $modelLabel = 'Gruppe';
    protected static ?string $pluralModelLabel = 'Gruppen';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    TextInput::make('name')
                        ->required(),
                    TextInput::make('description')
                        ->columnSpan(2)
                        ->label("Beschreibung"),
                    Toggle::make('moderated')
                        ->label("Gruppe ist moderiert"),
                    Select::make('grouptype')
                        ->label("Gruppentyp")
                        ->options([
                            "Projektgruppe" => "Projektgruppe"
                            , "Fachgruppe" => "Fachgruppe"
                            , "Netzwerktreffen" => "Netzwerktreffen"
                            , "Technische Gruppe" => "Technische Gruppe"
                            , "Organisatorische Gruppe" => "Organisatorische Gruppe"
                        ]),
                    TextInput::make('url')
                        ->label("URL")
                        ->prefix('https://'),
                    Toggle::make('has_mailinglist')
                        ->label("Hat eine Mailingliste"),
                    TextInput::make('mailinglisturl')
                        ->label("Mailinglisten-URL")
                        ->requiredIf('has_mailinglist', true),
                    TextInput::make('mailinglistpassword')
                        ->label("Mailinglisten-Passwort")
                        ->dehydrated(fn ($state) => filled($state))
                        ->password(),
                    Toggle::make('has_keycloakgroup')
                        ->label("Hat eine Keycloak-Gruppe"),
                    Select::make('keycloakgroup')
                        ->options(KeycloakHelper::get_groupselectoptions())
                        ->requiredIf('has_keycloakgroup', true)
                        ->searchable(),
                    Select::make('keycloakadmingroup')
                        ->options(KeycloakHelper::get_groupselectoptions())
                        ->requiredIf('moderated', true)
                        ->searchable(),
                ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('grouptype')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label("Gruppentyp"),
                TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->label("Beschreibung")
                    ->toggleable(),
                IconColumn::make('moderated')
                    ->sortable()
                    ->label("moderiert")
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('has_mailinglist')
                    ->sortable()
                    ->label("Mailingliste")
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('has_keycloakgroup')
                    ->label("Keycloak-Gruppe")
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('mailinglisturl')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('keycloakgroup')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('keycloakadmingroup')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make("grouptype")
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
            ->recordActions([
                ViewAction::make()->label("Anschauen"),
                EditAction::make()->label("Bearbeiten")
                ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label("Löschen"),
                    ForceDeleteBulkAction::make()->label("Endgültig löschen"),
                    RestoreBulkAction::make()->label("Wiederherstellen"),
                ]),
            ])
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [
            GroupmembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'view' => ViewGroup::route('/{record}'),
            'edit' => EditGroup::route('/{record}/edit'),
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

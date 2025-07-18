<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CredentialResource\Pages;
use App\Filament\Resources\CredentialResource\RelationManagers;
use App\Models\Credential;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CredentialResource extends Resource
{
    // Helper para obtener usuarios compartidos
    private static function getSharedUserIds($record)
    {
        return $record ? $record->shares()->where('shared_with_type', \App\Models\User::class)->pluck('shared_with_id')->toArray() : [];
    }

    // Helper para obtener grupos compartidos
    private static function getSharedGroupIds($record)
    {
        $record = $record ? $record->fresh('shares') : null;
        return $record ? $record->shares()->where('shared_with_type', \App\Models\Group::class)->pluck('shared_with_id')->toArray() : [];
    }

    // Helper para sincronizar compartidos
    private static function syncShares(array $data, Credential $record, $ownerId, $permission)
    {
        // Grupos
        if (isset($data['shared_with_group_ids'])) {
            $currentGroupIds = self::getSharedGroupIds($record);
            $newGroupIds = $data['shared_with_group_ids'] ?? [];
            $toAddGroups = array_diff($newGroupIds, $currentGroupIds);
            $toRemoveGroups = array_diff($currentGroupIds, $newGroupIds);
            foreach ($toAddGroups as $groupId) {
                app(\App\Services\CredentialShareService::class)
                    ->shareWithGroup($record->id, $groupId, $ownerId, $permission);
            }
            if (!empty($toRemoveGroups)) {
                \App\Models\CredentialShare::where('credential_id', $record->id)
                    ->where('shared_with_type', \App\Models\Group::class)
                    ->whereIn('shared_with_id', $toRemoveGroups)
                    ->delete();
            }
        }
        // Usuarios
        if (isset($data['shared_with_user_ids'])) {
            $currentIds = self::getSharedUserIds($record);
            $newIds = $data['shared_with_user_ids'] ?? [];
            $toAdd = array_diff($newIds, $currentIds);
            $toRemove = array_diff($currentIds, $newIds);
            foreach ($toAdd as $userId) {
                app(\App\Services\CredentialShareService::class)
                    ->shareWithUser($record->id, $userId, $ownerId, $permission);
            }
            if (!empty($toRemove)) {
                \App\Models\CredentialShare::where('credential_id', $record->id)
                    ->where('shared_with_type', \App\Models\User::class)
                    ->whereIn('shared_with_id', $toRemove)
                    ->delete();
            }
        }
    }
    protected static ?string $model = Credential::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Credentials';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->filters([])
            ->actions(self::getTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);

    }

    // Columnas
    private static function getTableColumns()
    {
        return [
            Tables\Columns\TextColumn::make('username')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('user.name')
                ->label('User')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('group.name')
                ->label('Group')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    // Acciones
    private static function getTableActions()
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('share')
                ->label('Share')
                ->icon('heroicon-o-share')
                ->visible(fn(Credential $record) => $record->canBeShared())
                ->form(self::getShareForm())
                ->action(function (array $data, Credential $record) {
                    $ownerId = Filament::auth()->user()->id;
                    $permission = $data['permission'];
                    self::syncShares($data, $record, $ownerId, $permission);
                })
                ->modalHeading('Share Credential'),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    // Formulario de compartición
    private static function getShareForm()
    {
        return [
            Forms\Components\MultiSelect::make('shared_with_user_ids')
                ->label('Share with User')
                ->options(function ($record) {
                    $allUsers = \App\Models\User::where('id', '!=', Filament::auth()->user()->id)
                        ->pluck('name', 'id')->toArray();
                    return $allUsers;
                })
                ->default(fn($record) => self::getSharedUserIds($record))
                ->searchable()
                ->helperText('Selecciona usuarios para compartir. Los ya compartidos aparecen seleccionados.'),
            Forms\Components\MultiSelect::make('shared_with_group_ids')
                ->label('Share with Group')
                ->options(function ($record) {
                    $allGroups = \App\Models\Group::pluck('name', 'id')->toArray();
                    return $allGroups;
                })
                ->default(fn($record) => self::getSharedGroupIds($record))
                ->searchable()
                ->helperText('Selecciona grupos para compartir. Los ya compartidos aparecen seleccionados.'),
            Forms\Components\Select::make('permission')
                ->label('Permission')
                ->options([
                    'read' => 'Read Only',
                    'write' => 'Read & Write',
                ])
                ->default('read')
                ->required(),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Filament::auth()->user()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCredentials::route('/'),
        ];
    }
}

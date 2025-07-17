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
            ->columns([
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('share')
                    ->label('Share')
                    ->icon('heroicon-o-share')
                    ->visible(fn(Credential $record) => $record->canBeShared())
                    ->form([
                        Forms\Components\Select::make('shared_with_user_id')
                            ->label('Share with User')
                            ->options(function () {
                                return \App\Models\User::where('id', '!=', Filament::auth()->user()->id)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                        Forms\Components\Select::make('shared_with_group_id')
                            ->label('Share with Group')
                            ->options(function () {
                                return \App\Models\Group::pluck('name', 'id');
                            })
                            ->searchable(),
                        Forms\Components\Select::make('permission')
                            ->label('Permission')
                            ->options([
                                'read' => 'Read Only',
                                'write' => 'Read & Write',
                            ])
                            ->default('read')
                            ->required(),
                    ])
                    ->action(function (array $data, Credential $record) {
                        $ownerId = Filament::auth()->user()->id;
                        $permission = $data['permission'];
                        if (!empty($data['shared_with_group_id'])) {
                            app(\App\Services\CredentialShareService::class)
                                ->shareWithGroup($record->id, $data['shared_with_group_id'], $ownerId, $permission);
                        }
                        if (!empty($data['shared_with_user_id'])) {
                            \App\Models\CredentialShare::updateOrCreate(
                                [
                                    'credential_id' => $record->id,
                                    'shared_with_user_id' => $data['shared_with_user_id'],
                                ],
                                [
                                    'shared_by_user_id' => $ownerId,
                                    'permission' => $permission,
                                ]
                            );
                        }
                    })
                    ->modalHeading('Share Credential'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCredentials::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SharedCredentialResource\Pages;
use App\Filament\Resources\SharedCredentialResource\RelationManagers;
use App\Models\Credential;
use App\Models\SharedCredential;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SharedCredentialResource extends Resource
{
    protected static ?string $model = Credential::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationLabel = 'Shared with Me';
    protected static ?string $navigationGroup = 'Credentials';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn($record) => !static::canEdit($record)),
                Forms\Components\TextInput::make('username')
                    ->label('Author')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn($record) => !static::canEdit($record)),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn($context) => $context === 'create')
                    ->revealable()
                    ->maxLength(255)
                    ->disabled(fn($record) => !static::canEdit($record)),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->maxLength(1000)
                    ->disabled(fn($record) => !static::canEdit($record)),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->disabled(fn($record) => !static::canEdit($record)),
            ]);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $currentUserId = Filament::auth()->user()->id;
        $share = $record->shares()->where('shared_with_user_id', $currentUserId)->first();
        return $share && $share->permission === 'write';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ViewEntry::make('credentials')
                    ->view('filament.infolists.credentials-with-copy')
                    ->state(function (Credential $record) {
                        return [
                            'title' => $record->title,
                            'username' => $record->username,
                            'password' => $record->password,
                            'description' => $record->description ?? 'No description',
                            'category' => $record->category->name ?? 'No category',
                        ];
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->state(fn($record) => strip_tags($record->description))
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\BadgeColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shared_by')
                    ->label('Shared by')
                    ->state(function (Credential $record) {
                        $currentUserId = Filament::auth()->user()->id;
                        $share = $record->shares()->where('shared_with_user_id', $currentUserId)->first();
                        return $share ? $share->sharedBy->name : 'Unknown';
                    })
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('permission')
                    ->label('Permission')
                    ->state(function (Credential $record) {
                        $currentUserId = Filament::auth()->user()->id;
                        $share = $record->shares()->where('shared_with_user_id', $currentUserId)->first();
                        return $share ? ucfirst($share->permission) : 'None';
                    })
                    ->badge()
                    ->color(function (Credential $record) {
                        $currentUserId = Filament::auth()->user()->id;
                        $share = $record->shares()->where('shared_with_user_id', $currentUserId)->first();
                        return $share && $share->permission === 'write' ? 'success' : 'info';
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('View Shared Credential'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(Credential $record) => static::canEdit($record)),
            ])
            ->bulkActions([

            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = Filament::auth()->user()->id;
        return parent::getEloquentQuery()
            ->whereHas('shares', function (Builder $query) {
                $query->where('shared_with_user_id', Filament::auth()->user()->id);
            })->orWhereHas('shares', function ($q) use ($userId) {
                $q->whereNotNull('shared_with_group_id')
                    ->whereHas('sharedWithGroup.users', function ($gq) use ($userId) {
                        $gq->where('users.id', $userId);
                    });
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSharedCredentials::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityViolationResource\Pages;
use App\Models\SecurityViolation;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class SecurityViolationResource extends Resource
{
    protected static ?string $model = SecurityViolation::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Security Violations';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('violation_type')
                    ->label('Type')
                    ->colors([
                        'danger',
                    ]),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityViolations::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

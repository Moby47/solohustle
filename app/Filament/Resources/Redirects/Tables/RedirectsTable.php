<?php

declare(strict_types=1);

namespace App\Filament\Resources\Redirects\Tables;

use App\Models\Redirect;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** The list of redirects in the dashboard. */
class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('from_path')
                    ->label('Old address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('to_path')
                    ->label('Goes to')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('hits')
                    ->label('Times used')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('try')
                    ->label('Try it')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Redirect $record): string => url($record->from_path))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No redirects yet')
            ->emptyStateDescription('When you change the address of a published page or article, a redirect is added here automatically.');
    }
}

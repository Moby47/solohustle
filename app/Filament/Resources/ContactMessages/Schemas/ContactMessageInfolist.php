<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Reading one message. */
class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('subject')->hiddenLabel()->size('lg')->weight('bold'),
                        TextEntry::make('message')->hiddenLabel()->prose()->formatStateUsing(fn (string $state): string => nl2br(e($state)))->html(),
                    ]),
                Section::make('From')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('email')->label('Email')->copyable(),
                        TextEntry::make('created_at')->label('Received')->dateTime('j M Y, H:i'),
                    ]),
            ]);
    }
}

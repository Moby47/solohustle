<?php

declare(strict_types=1);

namespace App\Filament\Resources\Redirects\Schemas;

use App\Models\Redirect;
use App\Rules\SiteLink;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/** The add/edit form for redirects. */
class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description('Use a redirect when you move or remove something, so old links and search results still land somewhere useful.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('from_path')
                            ->label('Old address')
                            ->placeholder('/old-page')
                            ->helperText('The address on this site that no longer exists. Starts with /.')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                fn (?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    if (! str_starts_with((string) $value, '/') || str_starts_with((string) $value, '//')) {
                                        $fail('Start with a single /, like /old-page.');

                                        return;
                                    }

                                    $taken = Redirect::where('from_path', Redirect::normalizePath($value))
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->exists();

                                    if ($taken) {
                                        $fail('There is already a redirect from this address.');
                                    }
                                },
                            ]),
                        TextInput::make('to_path')
                            ->label('New address')
                            ->placeholder('/new-page or https://…')
                            ->helperText('Where visitors should go instead.')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                new SiteLink,
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if (Redirect::normalizePath((string) $value) === Redirect::normalizePath((string) $get('from_path'))) {
                                        $fail('The new address must be different from the old one.');
                                    }
                                },
                            ]),
                    ]),
            ]);
    }
}

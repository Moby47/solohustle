<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use App\Models\Category;
use App\Models\Page;
use App\Rules\SiteLink;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/** Pick a link by name (a page, category or main section) instead of typing an address; any other address can still be added. */
final class LinkPicker
{
    public static function make(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->placeholder('Choose where it links to')
            ->searchable()
            ->options(fn (?string $state): array => self::options($state))
            ->rule(new SiteLink)
            ->createOptionForm([
                TextInput::make('address')
                    ->label('Web address')
                    ->helperText('A page on this site starting with / (like /about), or a full address starting with https://.')
                    ->placeholder('https://')
                    ->required()
                    ->rule(new SiteLink),
            ])
            ->createOptionUsing(fn (array $data): string => trim($data['address']))
            ->createOptionModalHeading('Link to another address');
    }

    /**
     * Grouped choices, plus the current value if it's a custom address.
     *
     * @return array<string, array<string, string>>
     */
    public static function options(?string $current = null): array
    {
        $groups = [
            'Main sections' => [
                '/' => 'Homepage',
                '/jobs' => 'Jobs',
                '/jobs/remote' => 'Remote jobs',
                '/search' => 'Search',
                '/publish' => 'Publish with us',
            ],
            'Blog categories' => Category::orderBy('sort')->pluck('name', 'slug')
                ->mapWithKeys(fn (string $name, string $slug): array => ["/{$slug}" => $name])->all(),
            'Pages' => Page::orderBy('title')->pluck('title', 'slug')
                ->mapWithKeys(fn (string $title, string $slug): array => ["/{$slug}" => $title])->all(),
        ];

        $known = array_merge(...array_values($groups));

        if (filled($current) && ! array_key_exists($current, $known)) {
            $groups['Other addresses'] = [$current => $current];
        }

        return array_filter($groups);
    }
}

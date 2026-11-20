<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subscribers\Pages;

use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\Subscriber;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Subscribers list, with a CSV download for use in any email tool or spreadsheet. */
class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Download CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    private function downloadCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Name', 'Status', 'Signed up on', 'Joined', 'Confirmed', 'Unsubscribed']);

            Subscriber::orderBy('created_at')->each(function (Subscriber $subscriber) use ($out): void {
                fputcsv($out, [
                    $subscriber->email,
                    $subscriber->name,
                    $subscriber->statusLabel(),
                    $subscriber->source,
                    $subscriber->created_at?->toDateTimeString(),
                    $subscriber->confirmed_at?->toDateTimeString(),
                    $subscriber->unsubscribed_at?->toDateTimeString(),
                ]);
            });

            fclose($out);
        }, 'subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

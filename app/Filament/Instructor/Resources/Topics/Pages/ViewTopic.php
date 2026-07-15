<?php

namespace App\Filament\Instructor\Resources\Topics\Pages;

use App\Filament\Instructor\Resources\Topics\TopicResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTopic extends ViewRecord
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

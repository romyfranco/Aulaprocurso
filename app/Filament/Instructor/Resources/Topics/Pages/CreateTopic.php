<?php

namespace App\Filament\Instructor\Resources\Topics\Pages;

use App\Filament\Instructor\Resources\Topics\TopicResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTopic extends CreateRecord
{
    protected static string $resource = TopicResource::class;
}

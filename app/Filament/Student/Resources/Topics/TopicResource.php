<?php

namespace App\Filament\Student\Resources\Topics;

use App\Filament\Resources\Topics\Schemas\TopicInfolist;
use App\Filament\Student\Resources\Enrollments\EnrollmentResource;
use App\Filament\Student\Resources\Topics\Pages\ViewTopic;
use App\Models\Topic;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $shouldRegisterNavigation = false;

    public static function infolist(Schema $schema): Schema
    {
        return TopicInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas(
            'courses.enrollments',
            fn (Builder $query) => $query
                ->where('student_id', auth()->id())
                ->whereIn('status', ['active', 'completed']),
        );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewTopic::route('/{record}'),
        ];
    }

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return EnrollmentResource::getUrl('index', isAbsolute: $isAbsolute, panel: $panel ?: 'student');
    }
}

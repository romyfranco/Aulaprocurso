<?php

namespace App\Filament\Instructor\Resources\QuizAttempts;

use App\Filament\Instructor\Resources\QuizAttempts\Pages\CreateQuizAttempt;
use App\Filament\Instructor\Resources\QuizAttempts\Pages\EditQuizAttempt;
use App\Filament\Instructor\Resources\QuizAttempts\Pages\ListQuizAttempts;
use App\Filament\Instructor\Resources\QuizAttempts\Pages\ViewQuizAttempt;
use App\Filament\Instructor\Resources\QuizAttempts\Schemas\QuizAttemptForm;
use App\Filament\Instructor\Resources\QuizAttempts\Schemas\QuizAttemptInfolist;
use App\Filament\Instructor\Resources\QuizAttempts\Tables\QuizAttemptsTable;
use App\Models\QuizAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Por calificar';

    protected static ?string $modelLabel = 'intento';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return QuizAttemptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuizAttemptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizAttemptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('quiz.topic.courses.instructors', fn (Builder $query) => $query->whereKey(auth()->id()));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizAttempts::route('/'),
            'create' => CreateQuizAttempt::route('/create'),
            'view' => ViewQuizAttempt::route('/{record}'),
            'edit' => EditQuizAttempt::route('/{record}/edit'),
        ];
    }
}

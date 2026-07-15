<?php

namespace App\Filament\Student\Resources\Quizzes;

use App\Filament\Student\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Student\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Student\Resources\Quizzes\Pages\ListQuizzes;
use App\Filament\Student\Resources\Quizzes\Pages\ViewQuiz;
use App\Filament\Student\Resources\Quizzes\Schemas\QuizForm;
use App\Filament\Student\Resources\Quizzes\Schemas\QuizInfolist;
use App\Filament\Student\Resources\Quizzes\Tables\QuizzesTable;
use App\Models\Quiz;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Evaluaciones pendientes';

    protected static ?string $modelLabel = 'evaluación';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return QuizForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuizInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizzesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('topic.courses.enrollments', fn (Builder $query) => $query->where('student_id', auth()->id()));
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
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'view' => ViewQuiz::route('/{record}'),
            'edit' => EditQuiz::route('/{record}/edit'),
        ];
    }
}

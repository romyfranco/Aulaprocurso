<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Matrícula')->icon('heroicon-o-user-plus')->schema([
                Select::make('student_id')->label('Estudiante')->relationship('student', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('role', 'student'))->searchable()->preload()->required(),
                Select::make('course_id')->label('Curso')->relationship('course', 'title')->searchable()->preload()->required(),
                DateTimePicker::make('enrolled_at')->label('Fecha de matrícula')->default(now())->required(),
                Select::make('status')->label('Estado')->options(['active' => 'Activa', 'completed' => 'Completada', 'dropped' => 'Retirada'])->native(false)->required()->default('active'),
            ])->columns(2),
            Section::make('Progreso')->icon('heroicon-o-chart-bar')->schema([
                TextInput::make('progress_percentage')->label('Avance')->numeric()->minValue(0)->maxValue(100)->suffix('%')->readOnly()->default(0),
                DateTimePicker::make('completed_at')->label('Completado el')->readOnly(),
            ])->columns(2),
        ]);
    }
}

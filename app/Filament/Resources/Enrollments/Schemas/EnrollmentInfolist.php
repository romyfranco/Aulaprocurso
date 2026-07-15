<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnrollmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Curso y estudiante')->icon('heroicon-o-academic-cap')->schema([
                TextEntry::make('course.title')->label('Curso')->size('lg')->weight('bold'),
                TextEntry::make('student.name')->label('Estudiante')->icon('heroicon-o-user'),
                TextEntry::make('status')->label('Estado')->badge()->color(fn ($state) => match ($state) {
                    'completed' => 'success','dropped' => 'danger',default => 'info'
                }),
            ])->columns(3),
            Section::make('Progreso')->icon('heroicon-o-chart-pie')->schema([
                TextEntry::make('progress_percentage')->label('Avance')->suffix('%')->badge()->color(fn ($state) => $state >= 100 ? 'success' : ($state < 40 ? 'danger' : 'primary')),
                TextEntry::make('enrolled_at')->label('Matrícula')->dateTime('d M Y'),
                TextEntry::make('completed_at')->label('Finalización')->dateTime('d M Y')->placeholder('En curso'),
            ])->columns(3),
        ]);
    }
}

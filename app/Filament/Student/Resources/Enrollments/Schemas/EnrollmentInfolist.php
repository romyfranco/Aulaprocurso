<?php

namespace App\Filament\Student\Resources\Enrollments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EnrollmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('student.name')
                    ->label('Student'),
                TextEntry::make('course.title')
                    ->label('Course'),
                TextEntry::make('enrolled_at')
                    ->dateTime(),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('progress_percentage')
                    ->numeric(),
                TextEntry::make('status'),
            ]);
    }
}

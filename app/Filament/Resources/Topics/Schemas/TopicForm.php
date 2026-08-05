<?php

namespace App\Filament\Resources\Topics\Schemas;

use App\Models\Course;
use App\Models\Topic;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->icon('heroicon-o-document-text')->schema([
                TextInput::make('title')->label('Título')->required(),
                Select::make('course_id')
                    ->label('Curso')
                    ->options(function (): array {
                        $query = Course::query()->orderBy('title');

                        if (auth()->user()?->role === 'instructor') {
                            $query
                                ->where('status', '!=', 'archived')
                                ->whereHas('instructors', fn ($instructors) => $instructors->whereKey(auth()->id()));
                        }

                        return $query->get()->mapWithKeys(fn (Course $course): array => [
                            $course->id => $course->title.' · '.match ($course->status) {
                                'published' => 'Publicado',
                                'draft' => 'Borrador',
                                default => 'Archivado',
                            },
                        ])->all();
                    })
                    ->afterStateHydrated(function (Select $component, ?Topic $record): void {
                        if ($record) {
                            $component->state($record->courses()->value('courses.id'));
                        }
                    })
                    ->helperText('El tema se añadirá automáticamente al final del curso seleccionado.')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required()
                    ->dehydrated(false),
                Textarea::make('description')->label('Resumen')->rows(3)->required()->columnSpanFull(),
                Select::make('created_by')->label('Autor')->relationship('creator', 'name')->default(fn () => auth()->id())->required()->searchable(),
            ])->columns(2),
            Section::make('Contenido del tema')->icon('heroicon-o-pencil-square')->schema([
                RichEditor::make('content')->label('Lección')->required()->columnSpanFull(),
            ]),
            Section::make('Recursos multimedia')->icon('heroicon-o-paper-clip')->description('Imágenes, videos y documentos de apoyo para la lección.')->schema([
                SpatieMediaLibraryFileUpload::make('images')->label('Imágenes')->collection('images')->image()->multiple()->reorderable(),
                SpatieMediaLibraryFileUpload::make('videos')->label('Videos')->collection('videos')->acceptedFileTypes(['video/mp4', 'video/webm'])->multiple(),
                SpatieMediaLibraryFileUpload::make('documents')->label('Documentos y presentaciones')->collection('documents')->acceptedFileTypes(['application/pdf', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])->multiple(),
            ])->columns(3),
            Section::make('Presentación PDF')
                ->icon('heroicon-o-document-chart-bar')
                ->description('Exporta el PowerPoint como PDF. Solo habrá una presentación activa por tema.')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('presentation_pdf')
                        ->label('Presentación PDF')
                        ->collection('presentation_pdf')
                        ->disk('local')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize((int) ceil(config('presentations.pdf_max_bytes') / 1024))
                        ->previewable(false)
                        ->openable(false)
                        ->downloadable(false)
                        ->helperText('Máximo 100 MB. Al cargar otro PDF se reemplazará la presentación anterior.'),
                ]),
            Section::make('Presentación Reveal.js')
                ->icon('heroicon-o-presentation-chart-bar')
                ->description('Sube un ZIP con un único index.html. Puede estar en la raíz o dentro de una sola carpeta superior.')
                ->schema([
                    FileUpload::make('pending_reveal_archive')
                        ->label('Paquete Reveal.js (.zip)')
                        ->disk(config('reveal.disk'))
                        ->directory('reveal/archives')
                        ->visibility('private')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize((int) ceil(config('reveal.archive_max_bytes') / 1024))
                        ->storeFileNamesIn('pending_reveal_original_name')
                        ->helperText('Máximo 100 MB. Una carga nueva se activará solo después de superar todas las validaciones.'),
                    Placeholder::make('reveal_processing_status')
                        ->label('Estado')
                        ->content(function (?Topic $record): string {
                            $upload = $record?->latestRevealUpload;

                            return match ($upload?->status) {
                                'processing' => 'Procesando la nueva presentación…',
                                'ready' => 'Presentación disponible.',
                                'failed' => 'La última carga falló: '.$upload->error_message,
                                default => 'Todavía no hay una presentación cargada.',
                            };
                        }),
                ])->columns(2),
        ]);
    }
}

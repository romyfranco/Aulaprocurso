<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Configuración')->icon('heroicon-o-adjustments-horizontal')->schema([
                Select::make('topic_id')->label('Tema')->relationship('topic', 'title')->searchable()->preload()->required(),
                TextInput::make('title')->label('Título')->required()->default('Evaluación del tema'),
                Textarea::make('instructions')->label('Instrucciones')->rows(3)->columnSpanFull(),
                TextInput::make('passing_score')->label('Puntaje aprobatorio')->numeric()->minValue(1)->maxValue(100)->suffix('%')->required()->default(70),
                TextInput::make('max_attempts')->label('Intentos base')->numeric()->minValue(1)->required()->default(2),
            ])->columns(2),
            Section::make('Preguntas')->icon('heroicon-o-question-mark-circle')->description('Crea preguntas al estilo de un formulario; las respuestas abiertas quedarán por calificar.')->schema([
                Repeater::make('questions')->label('')->relationship()->orderColumn('order')->schema([
                    Textarea::make('question_text')->label('Pregunta')->required()->columnSpanFull(),
                    Select::make('question_type')->label('Tipo')->options(['multiple_choice' => 'Opción múltiple', 'true_false' => 'Verdadero / falso', 'short_answer' => 'Respuesta corta', 'essay' => 'Ensayo'])->required()->native(false),
                    TextInput::make('points')->label('Puntos')->numeric()->minValue(1)->required()->default(10),
                    Repeater::make('options')->label('Opciones de respuesta')->relationship()->schema([
                        TextInput::make('option_text')->label('Opción')->required(),
                        Select::make('is_correct')->label('Respuesta')->options([0 => 'Incorrecta', 1 => 'Correcta'])->required()->native(false),
                    ])->columns(2)->columnSpanFull()->defaultItems(2),
                ])->columns(2)->collapsible()->itemLabel(fn (array $state) => $state['question_text'] ?? 'Nueva pregunta')->addActionLabel('Agregar pregunta')->columnSpanFull(),
            ]),
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizFormBehaviorTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_questions_hide_and_clear_answer_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['short_answer', 'essay'] as $questionType) {
            $component = Livewire::actingAs($admin)
                ->test(CreateQuiz::class)
                ->fillForm([
                    'questions' => [[
                        'question_text' => 'Explica el procedimiento.',
                        'question_type' => 'multiple_choice',
                        'points' => 10,
                        'options' => [
                            ['option_text' => 'Opción A', 'is_correct' => true],
                            ['option_text' => 'Opción B', 'is_correct' => false],
                        ],
                    ]],
                ]);

            $questionKey = array_key_first($component->get('data.questions'));

            $component
                ->assertFormFieldVisible("questions.{$questionKey}.options")
                ->set("data.questions.{$questionKey}.question_type", $questionType)
                ->assertFormFieldHidden("questions.{$questionKey}.options")
                ->assertSet("data.questions.{$questionKey}.options", []);
        }
    }
}

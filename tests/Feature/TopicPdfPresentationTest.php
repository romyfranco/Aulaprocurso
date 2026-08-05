<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileUnacceptableForCollection;
use Tests\TestCase;

class TopicPdfPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('presentations.disk'));
    }

    public function test_livewire_temporary_upload_supports_the_pdf_limit(): void
    {
        $expectedKilobytes = (int) ceil(config('presentations.pdf_max_bytes') / 1024);

        $this->assertContains("max:{$expectedKilobytes}", config('livewire.temporary_file_upload.rules'));
    }

    public function test_pdf_collection_is_private_single_file_and_rejects_other_formats(): void
    {
        $topic = $this->scenario()['topics'][0];
        $collection = $topic->getMediaCollection('presentation_pdf');

        $this->assertSame(config('presentations.disk'), $collection->diskName);
        $this->assertTrue($collection->singleFile);
        $this->assertSame(['application/pdf'], $collection->acceptsMimeTypes);
        $this->assertSame(100 * 1024 * 1024, config('presentations.pdf_max_bytes'));

        $this->expectException(FileUnacceptableForCollection::class);

        $topic->addMedia(UploadedFile::fake()->create('presentacion.txt', 10, 'text/plain'))
            ->toMediaCollection('presentation_pdf', config('presentations.disk'));
    }

    public function test_authorized_student_can_open_the_viewer_and_stream_the_pdf(): void
    {
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];
        $this->addPdf($topic);

        $this->actingAs($scenario['student'])
            ->get('/student/topics/'.$topic->id)
            ->assertOk()
            ->assertSee('Presentación PDF')
            ->assertSee('Ver presentación')
            ->assertSee(route('topics.pdf-presentation.view', $topic), false);

        $this->actingAs($scenario['student'])
            ->get(route('topics.pdf-presentation.view', $topic))
            ->assertOk()
            ->assertSee('Atrás')
            ->assertSee('Siguiente')
            ->assertSee('Página')
            ->assertSee('data-pdf-viewer', false)
            ->assertSee(route('topics.pdf-presentation.file', $topic), false);

        $fileResponse = $this->actingAs($scenario['student'])
            ->get(route('topics.pdf-presentation.file', $topic))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Accept-Ranges', 'bytes');

        $this->assertStringContainsString('private', $fileResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $fileResponse->headers->get('Cache-Control'));

        $this->actingAs($scenario['student'])
            ->withHeader('Range', 'bytes=0-3')
            ->get(route('topics.pdf-presentation.file', $topic))
            ->assertStatus(206)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Range');
    }

    public function test_locked_unenrolled_and_guest_users_cannot_open_the_pdf(): void
    {
        $scenario = $this->scenario();
        $lockedTopic = $scenario['topics'][1];
        $this->addPdf($lockedTopic);
        $outsider = User::factory()->create(['role' => 'student']);

        $this->actingAs($scenario['student'])
            ->get(route('topics.pdf-presentation.view', $lockedTopic))
            ->assertForbidden();

        $this->actingAs($scenario['student'])
            ->get(route('topics.pdf-presentation.file', $lockedTopic))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('topics.pdf-presentation.view', $lockedTopic))
            ->assertForbidden();

        auth()->logout();

        $this->get(route('topics.pdf-presentation.view', $lockedTopic))
            ->assertRedirect(route('login'));
    }

    public function test_admin_and_related_instructor_can_open_the_presentation(): void
    {
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];
        $this->addPdf($topic);

        $this->actingAs($scenario['admin'])
            ->get(route('topics.pdf-presentation.view', $topic))
            ->assertOk();

        $this->actingAs($scenario['instructor'])
            ->get(route('topics.pdf-presentation.file', $topic))
            ->assertOk();
    }

    public function test_missing_presentation_returns_not_found_and_does_not_show_the_button(): void
    {
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];

        $this->actingAs($scenario['student'])
            ->get('/student/topics/'.$topic->id)
            ->assertOk()
            ->assertDontSee('Ver presentación');

        $this->actingAs($scenario['student'])
            ->get(route('topics.pdf-presentation.view', $topic))
            ->assertNotFound();
    }

    public function test_replacing_or_deleting_the_pdf_removes_the_old_file(): void
    {
        $topic = $this->scenario()['topics'][0];
        $first = $this->addPdf($topic, 'primera.pdf');
        $firstPath = $first->getPathRelativeToRoot();
        Storage::disk(config('presentations.disk'))->assertExists($firstPath);

        $second = $this->addPdf($topic, 'segunda.pdf');
        $secondPath = $second->getPathRelativeToRoot();

        $this->assertDatabaseMissing('media', ['id' => $first->id]);
        Storage::disk(config('presentations.disk'))->assertMissing($firstPath);
        Storage::disk(config('presentations.disk'))->assertExists($secondPath);

        $topic->delete();

        $this->assertDatabaseMissing('media', ['id' => $second->id]);
        Storage::disk(config('presentations.disk'))->assertMissing($secondPath);
    }

    public function test_opening_the_viewer_does_not_change_student_progress(): void
    {
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];
        $this->addPdf($topic);

        $this->assertDatabaseCount('quiz_attempts', 0);

        $this->actingAs($scenario['student'])->get(route('topics.pdf-presentation.view', $topic))->assertOk();
        $this->actingAs($scenario['student'])->get(route('topics.pdf-presentation.file', $topic))->assertOk();

        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->assertSame('active', $scenario['enrollment']->fresh()->status);
    }

    private function scenario(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Curso PDF',
            'slug' => 'curso-pdf-'.Str::random(8),
            'description' => 'Contenido',
            'status' => 'published',
            'created_by' => $admin->id,
        ]);
        $course->instructors()->attach($instructor->id, ['assigned_at' => now()]);
        $topics = collect();

        foreach ([1, 2] as $order) {
            $topic = Topic::create([
                'title' => "Tema PDF {$order}",
                'description' => 'Resumen',
                'content' => '<p>Contenido</p>',
                'created_by' => $instructor->id,
            ]);
            $course->topics()->attach($topic->id, ['order' => $order]);
            Quiz::create([
                'topic_id' => $topic->id,
                'title' => "Evaluación {$order}",
                'passing_score' => 70,
                'max_attempts' => 2,
            ]);
            $topics->push($topic);
        }

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        return compact('admin', 'instructor', 'student', 'course', 'topics', 'enrollment');
    }

    private function addPdf(Topic $topic, string $name = 'presentacion.pdf')
    {
        return $topic->addMedia(UploadedFile::fake()->createWithContent($name, $this->pdfContents()))
            ->toMediaCollection('presentation_pdf', config('presentations.disk'));
    }

    private function pdfContents(): string
    {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";
    }
}

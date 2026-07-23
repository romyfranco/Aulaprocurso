<?php

namespace Tests\Feature;

use App\Exceptions\InvalidRevealArchive;
use App\Http\Controllers\ServeRevealAssetController;
use App\Jobs\ProcessRevealPresentation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RevealPresentation;
use App\Models\Topic;
use App\Models\User;
use App\Services\RevealArchiveExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class RevealPresentationTest extends TestCase
{
    public function test_livewire_temporary_upload_accepts_the_configured_reveal_archive_limit(): void
    {
        $expectedKilobytes = (int) ceil(config('reveal.archive_max_bytes') / 1024);

        $this->assertContains(
            "max:{$expectedKilobytes}",
            config('livewire.temporary_file_upload.rules')
        );
        $this->assertGreaterThanOrEqual(20, config('livewire.temporary_file_upload.max_upload_time'));
    }

    public function test_asset_rate_limit_can_serve_the_maximum_number_of_deck_files(): void
    {
        $this->assertGreaterThan(
            config('reveal.max_files'),
            config('reveal.rate_limit_per_minute')
        );
    }

    public function test_loader_bridge_is_inserted_before_the_final_body_tag(): void
    {
        $controller = app(ServeRevealAssetController::class);
        $method = new \ReflectionMethod($controller, 'prepareHtml');
        $method->setAccessible(true);
        $html = '<html><body><script>const template = "</body>";</script></body></html>';

        $prepared = $method->invoke($controller, $html, str_repeat('a', 64), 'index.html');
        $templatePosition = strpos($prepared, 'const template = "</body>";');
        $bridgePosition = strpos($prepared, '<script data-voranapro-reveal-bridge>');
        $finalBodyPosition = strripos($prepared, '</body>');

        $this->assertIsInt($templatePosition);
        $this->assertIsInt($bridgePosition);
        $this->assertIsInt($finalBodyPosition);
        $this->assertGreaterThan($templatePosition, $bridgePosition);
        $this->assertLessThan($finalBodyPosition, $bridgePosition);
    }

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('reveal');
        Cache::flush();
    }

    public function test_extracts_a_deck_from_the_root_or_one_wrapper_folder(): void
    {
        foreach ([
            'root.zip' => [
                'index.html' => $this->deckHtml(),
                'dist/reveal.css' => '.reveal { color: #fff; }',
            ],
            'wrapper.zip' => [
                'curso/index.html' => $this->deckHtml(),
                'curso/dist/reveal.css' => '.reveal { color: #fff; }',
            ],
        ] as $archive => $files) {
            $path = $this->storeZip($archive, $files);
            $destination = Storage::disk('reveal')->path('reveal/test/'.pathinfo($archive, PATHINFO_FILENAME));

            $metadata = app(RevealArchiveExtractor::class)->extract(Storage::disk('reveal')->path($path), $destination);

            $this->assertSame('index.html', $metadata['entry_path']);
            $this->assertSame(2, $metadata['file_count']);
            $this->assertFileExists($destination.'/index.html');
            $this->assertFileExists($destination.'/dist/reveal.css');
        }
    }

    public function test_rejects_unsafe_or_ambiguous_archives(): void
    {
        $cases = [
            'traversal.zip' => ['../escape.js' => 'alert(1)', 'index.html' => $this->deckHtml()],
            'executable.zip' => ['index.html' => $this->deckHtml(), 'payload.php' => '<?php echo 1;'],
            'multiple-index.zip' => ['index.html' => $this->deckHtml(), 'other/index.html' => $this->deckHtml()],
            'absolute-reference.zip' => ['index.html' => '<html><script src="/reveal.js"></script></html>'],
            'http-reference.zip' => ['index.html' => '<html><script src="http://example.test/reveal.js"></script></html>'],
            'css-root-reference.zip' => ['index.html' => $this->deckHtml(), 'theme.css' => 'body { background: url(/background.png); }'],
            'nested-html-reference.zip' => ['index.html' => $this->deckHtml(), 'pages/detail.html' => '<html><img src="../outside.png"></html>'],
        ];

        foreach ($cases as $archive => $files) {
            $path = $this->storeZip($archive, $files);

            try {
                app(RevealArchiveExtractor::class)->extract(
                    Storage::disk('reveal')->path($path),
                    Storage::disk('reveal')->path('reveal/rejected/'.pathinfo($archive, PATHINFO_FILENAME)),
                );
                $this->fail("{$archive} debió ser rechazado.");
            } catch (InvalidRevealArchive) {
                $this->assertTrue(true);
            }
        }

        $symlink = $this->storeZip(
            'symlink.zip',
            ['index.html' => $this->deckHtml(), 'link.js' => 'target.js'],
            ['link.js' => 0120777],
        );

        $this->expectException(InvalidRevealArchive::class);
        app(RevealArchiveExtractor::class)->extract(
            Storage::disk('reveal')->path($symlink),
            Storage::disk('reveal')->path('reveal/rejected/symlink'),
        );
    }

    public function test_rejects_archives_that_exceed_configured_limits(): void
    {
        config()->set('reveal.max_files', 1);
        $tooManyFiles = $this->storeZip('too-many.zip', [
            'index.html' => $this->deckHtml(),
            'dist/reveal.css' => '.reveal {}',
        ]);

        try {
            app(RevealArchiveExtractor::class)->extract(
                Storage::disk('reveal')->path($tooManyFiles),
                Storage::disk('reveal')->path('reveal/rejected/too-many'),
            );
            $this->fail('El límite de archivos debió impedir la extracción.');
        } catch (InvalidRevealArchive) {
            $this->assertTrue(true);
        }

        config()->set('reveal.max_files', 5000);
        config()->set('reveal.extracted_max_bytes', 10);
        $tooLarge = $this->storeZip('too-large.zip', ['index.html' => $this->deckHtml()]);

        $this->expectException(InvalidRevealArchive::class);
        app(RevealArchiveExtractor::class)->extract(
            Storage::disk('reveal')->path($tooLarge),
            Storage::disk('reveal')->path('reveal/rejected/too-large'),
        );
    }

    public function test_a_valid_upload_is_activated_and_replaces_the_old_version_atomically(): void
    {
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];
        $old = $this->readyPresentation($topic, $scenario['instructor'], 'old-version');
        $archive = $this->storeZip('new.zip', [
            'deck/index.html' => $this->deckHtml('Versión nueva'),
            'deck/dist/reveal.css' => '.reveal { color: #fff; }',
        ]);
        $new = RevealPresentation::create([
            'topic_id' => $topic->id,
            'uploaded_by' => $scenario['instructor']->id,
            'version' => (string) Str::uuid(),
            'status' => 'processing',
            'original_name' => 'new.zip',
            'archive_path' => $archive,
            'archive_size' => Storage::disk('reveal')->size($archive),
        ]);

        app(ProcessRevealPresentation::class, ['presentation' => $new])->handle(app(RevealArchiveExtractor::class));

        $this->assertSame($new->id, $topic->refresh()->active_reveal_presentation_id);
        $this->assertSame('ready', $new->refresh()->status);
        $this->assertDatabaseMissing('reveal_presentations', ['id' => $old->id]);
        Storage::disk('reveal')->assertMissing('reveal/decks/old-version');
        Storage::disk('reveal')->assertExists($new->storage_path.'/index.html');
    }

    public function test_a_failed_upload_keeps_the_current_presentation_active(): void
    {
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];
        $old = $this->readyPresentation($topic, $scenario['instructor'], 'working-version');
        $archive = $this->storeZip('invalid.zip', ['readme.txt' => 'Sin index']);
        $new = RevealPresentation::create([
            'topic_id' => $topic->id,
            'uploaded_by' => $scenario['instructor']->id,
            'version' => (string) Str::uuid(),
            'status' => 'processing',
            'original_name' => 'invalid.zip',
            'archive_path' => $archive,
            'archive_size' => Storage::disk('reveal')->size($archive),
        ]);

        app(ProcessRevealPresentation::class, ['presentation' => $new])->handle(app(RevealArchiveExtractor::class));

        $this->assertSame($old->id, $topic->refresh()->active_reveal_presentation_id);
        $this->assertSame('failed', $new->refresh()->status);
        Storage::disk('reveal')->assertExists($old->storage_path.'/index.html');
    }

    public function test_students_receive_a_temporary_token_only_for_unlocked_topics(): void
    {
        $scenario = $this->scenario();
        [$firstTopic, $secondTopic] = $scenario['topics'];
        $first = $this->readyPresentation($firstTopic, $scenario['instructor'], 'first-version');
        $this->readyPresentation($secondTopic, $scenario['instructor'], 'second-version');

        $response = $this->actingAs($scenario['student'])
            ->get('http://localhost'.route('topics.presentation.launch', $firstTopic, false));
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('http://slides.example.test/p/', $location);
        preg_match('#/p/([A-Za-z0-9]{64})/index\.html$#', $location, $matches);
        $token = $matches[1] ?? null;
        $this->assertNotNull($token);

        $this->get('http://slides.example.test/p/'.$token)
            ->assertRedirect('http://slides.example.test/p/'.$token.'/index.html');

        $asset = $this->get('http://slides.example.test/p/'.$token.'/index.html');
        $asset
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Content-Security-Policy')
            ->assertHeaderMissing('Set-Cookie');
        $asset->assertSee('<div class="reveal">', false);
        $asset->assertSee('<base href="http://slides.example.test/p/'.$token.'/">', false);
        $asset->assertSee('data-voranapro-inlined-stylesheet="dist/reveal.css"', false);
        $asset->assertSee('data-voranapro-inlined-script="dist/reveal.js"', false);
        $asset->assertDontSee('<script src="dist/reveal.js"></script>', false);
        $asset->assertSee('data-voranapro-reveal-bridge', false);
        $asset->assertSee('id="voranapro-reveal-loader"', false);
        $asset->assertSee('voranapro:reveal-progress', false);
        $asset->assertSee('voranapro:reveal-prepared', false);
        $asset->assertSee('voranapro:reveal-visible', false);
        $asset->assertSee('revealStylesAreApplied', false);
        $asset->assertSee('ensureRevealRuntime', false);
        $asset->assertSee('voranapro_script_retry', false);
        $asset->assertSee('motor y sus complementos', false);
        $asset->assertSee('stabilizeLayout', false);
        $asset->assertSee('voranapro:reveal-ready', false);
        $asset->assertSee('voranapro:reveal-layout', false);

        $this->withHeader('Range', 'bytes=0-3')
            ->get('http://slides.example.test/p/'.$token.'/media/sample.mp4')
            ->assertStatus(206)
            ->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Range', 'bytes 0-3/10');

        $this->actingAs($scenario['student'])
            ->get('http://localhost'.route('topics.presentation.launch', $secondTopic, false))
            ->assertForbidden();

        QuizAttempt::create([
            'quiz_id' => $scenario['quizzes'][0]->id,
            'student_id' => $scenario['student']->id,
            'enrollment_id' => $scenario['enrollment']->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'score' => 90,
        ]);

        $this->actingAs($scenario['student'])
            ->get('http://localhost'.route('topics.presentation.launch', $secondTopic, false))
            ->assertRedirect();

        $this->assertSame('ready', $first->fresh()->status);
    }

    public function test_tokens_expire_and_cannot_escape_the_deck_directory(): void
    {
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];
        $this->readyPresentation($topic, $scenario['instructor'], 'secure-version');

        $location = $this->actingAs($scenario['student'])
            ->get(route('topics.presentation.launch', $topic))
            ->headers->get('Location');
        preg_match('#/p/([A-Za-z0-9]{64})/index\.html$#', $location, $matches);
        $token = $matches[1];

        $this->get('http://slides.example.test/p/'.$token.'/../.env')->assertNotFound();

        Cache::flush();
        $this->get('http://slides.example.test/p/'.$token.'/index.html')->assertNotFound();
    }

    public function test_the_slides_subdomain_only_serves_tokenized_presentation_assets(): void
    {
        foreach (['/', '/login', '/admin/login', '/unrelated/path'] as $path) {
            $this->get('http://slides.example.test'.$path)
                ->assertNotFound()
                ->assertHeaderMissing('Set-Cookie');
        }

        $this->get('http://localhost/')
            ->assertOk()
            ->assertSee('VoranaPro');
    }

    public function test_student_topic_pages_respect_course_progress(): void
    {
        $scenario = $this->scenario();
        [$firstTopic, $secondTopic] = $scenario['topics'];
        $this->readyPresentation($firstTopic, $scenario['instructor'], 'student-view');

        $this->actingAs($scenario['student'])
            ->get('/student/enrollments/'.$scenario['enrollment']->id)
            ->assertOk()
            ->assertSee($firstTopic->title)
            ->assertSee($secondTopic->title)
            ->assertSee('Ver tema');

        $this->actingAs($scenario['student'])
            ->get('/student/topics/'.$firstTopic->id)
            ->assertOk()
            ->assertSee('Abrir en otra pestaña')
            ->assertSee('Preparando la presentación')
            ->assertSee('Reintentar')
            ->assertSee('position:absolute;inset:0', false);

        $this->actingAs($scenario['student'])
            ->get('/student/topics/'.$secondTopic->id)
            ->assertForbidden();
    }

    public function test_non_enrolled_students_cannot_launch_a_presentation(): void
    {
        $scenario = $this->scenario();
        $outsider = User::factory()->create(['role' => 'student']);
        $topic = $scenario['topics'][0];
        $this->readyPresentation($topic, $scenario['instructor'], 'private-version');

        $this->actingAs($outsider)
            ->get(route('topics.presentation.launch', $topic))
            ->assertForbidden();
    }

    public function test_topic_media_is_visible_and_servable_from_the_public_disk(): void
    {
        Storage::fake('public');
        $scenario = $this->scenario();
        $topic = $scenario['topics'][0];

        $image = $topic->addMedia(UploadedFile::fake()->image('sistema-frenado.jpg', 320, 180))
            ->toMediaCollection('images');
        $document = $topic->addMedia(UploadedFile::fake()->createWithContent('manual.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF"))
            ->toMediaCollection('documents');

        $this->actingAs($scenario['student'])
            ->get('/student/topics/'.$topic->id)
            ->assertOk()
            ->assertSee('Recursos del tema')
            ->assertSee('sistema-frenado')
            ->assertSee('manual');

        $this->get(parse_url($image->getUrl(), PHP_URL_PATH))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get(parse_url($document->getUrl(), PHP_URL_PATH))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    private function scenario(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Curso Reveal',
            'slug' => 'curso-reveal-'.Str::random(8),
            'description' => 'Contenido',
            'status' => 'published',
            'created_by' => $admin->id,
        ]);
        $course->instructors()->attach($instructor->id, ['assigned_at' => now()]);
        $topics = collect();
        $quizzes = collect();

        foreach ([1, 2] as $order) {
            $topic = Topic::create([
                'title' => "Tema {$order}",
                'description' => 'Resumen',
                'content' => '<p>Contenido del tema</p>',
                'created_by' => $instructor->id,
            ]);
            $course->topics()->attach($topic->id, ['order' => $order]);
            $topics->push($topic);
            $quizzes->push(Quiz::create([
                'topic_id' => $topic->id,
                'title' => "Quiz {$order}",
                'passing_score' => 70,
                'max_attempts' => 2,
            ]));
        }

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        return compact('admin', 'instructor', 'student', 'course', 'topics', 'quizzes', 'enrollment');
    }

    private function readyPresentation(Topic $topic, User $uploader, string $version): RevealPresentation
    {
        $storagePath = 'reveal/decks/'.$version;
        $html = str_replace('</body>', '<script src="dist/reveal.js"></script></body>', $this->deckHtml());
        Storage::disk('reveal')->put($storagePath.'/index.html', $html);
        Storage::disk('reveal')->put($storagePath.'/dist/reveal.css', '.reveal { color: #fff; }');
        Storage::disk('reveal')->put($storagePath.'/dist/reveal.js', 'window.Reveal = window.Reveal || {};');
        Storage::disk('reveal')->put($storagePath.'/media/sample.mp4', '0123456789');
        Storage::disk('reveal')->put('reveal/archives/'.$version.'.zip', 'archive');

        $presentation = RevealPresentation::create([
            'topic_id' => $topic->id,
            'uploaded_by' => $uploader->id,
            'version' => $version,
            'status' => 'ready',
            'original_name' => $version.'.zip',
            'archive_path' => 'reveal/archives/'.$version.'.zip',
            'storage_path' => $storagePath,
            'entry_path' => 'index.html',
            'archive_size' => 7,
            'extracted_size' => 100,
            'file_count' => 4,
            'processed_at' => now(),
        ]);

        $topic->forceFill(['active_reveal_presentation_id' => $presentation->id])->saveQuietly();

        return $presentation;
    }

    /**
     * @param  array<string, string>  $files
     */
    private function storeZip(string $name, array $files, array $unixModes = []): string
    {
        $temporary = tempnam(sys_get_temp_dir(), 'reveal-test-');
        $zip = new ZipArchive;
        $zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $path => $contents) {
            $zip->addFromString($path, $contents);

            if (isset($unixModes[$path])) {
                $zip->setExternalAttributesName($path, ZipArchive::OPSYS_UNIX, $unixModes[$path] << 16);
            }
        }

        $zip->close();
        $storagePath = 'reveal/archives/'.$name;
        Storage::disk('reveal')->put($storagePath, file_get_contents($temporary));
        unlink($temporary);

        return $storagePath;
    }

    private function deckHtml(string $title = 'Presentación de prueba'): string
    {
        return <<<HTML
        <!doctype html>
        <html lang="es">
        <head><link rel="stylesheet" href="dist/reveal.css"></head>
        <body><div class="reveal"><div class="slides"><section><h1>{$title}</h1></section></div></div></body>
        </html>
        HTML;
    }
}

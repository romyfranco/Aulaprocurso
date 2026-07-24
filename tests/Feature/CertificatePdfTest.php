<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificatePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_generate_and_download_an_existing_certificate(): void
    {
        Storage::fake('public');
        $this->seed();

        $student = User::where('role', 'student')->firstOrFail();
        $certificate = Certificate::firstOrFail();

        $this->assertNull($certificate->pdf_path);

        $response = $this->actingAs($student)
            ->get(route('certificates.download', $certificate))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->streamedContent());

        $certificate->refresh();
        $this->assertSame("certificates/{$certificate->certificate_code}.pdf", $certificate->pdf_path);
        Storage::disk('public')->assertExists($certificate->pdf_path);
    }

    public function test_student_cannot_download_another_students_certificate(): void
    {
        Storage::fake('public');
        $this->seed();

        $certificate = Certificate::firstOrFail();
        $otherStudent = User::factory()->create(['role' => 'student']);

        $this->actingAs($otherStudent)
            ->get(route('certificates.download', $certificate))
            ->assertForbidden();
    }

    public function test_download_action_is_visible_before_the_pdf_exists(): void
    {
        Storage::fake('public');
        $this->seed();

        $student = User::where('role', 'student')->firstOrFail();
        $certificate = Certificate::firstOrFail();

        $this->actingAs($student)
            ->get('/student/certificates')
            ->assertOk()
            ->assertSee('Descargar PDF');

        $this->actingAs($student)
            ->get('/student/certificates/'.$certificate->certificate_code)
            ->assertOk()
            ->assertSee('Descargar PDF');
    }
}

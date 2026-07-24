<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    public function issue(Enrollment $enrollment): Certificate
    {
        $certificate = Certificate::firstOrCreate(['enrollment_id' => $enrollment->id], [
            'student_id' => $enrollment->student_id, 'course_id' => $enrollment->course_id,
            'certificate_code' => 'EDU-'.now()->format('Y').'-'.Str::upper(Str::random(10)), 'issued_at' => now(),
        ]);
        if (! $certificate->qr_code_path) {
            $path = "certificates/{$certificate->certificate_code}.svg";
            $options = new QROptions(['outputType' => QROutputInterface::MARKUP_SVG, 'outputBase64' => false, 'svgUseFillAttributes' => true]);
            Storage::disk('public')->put($path, (new QRCode($options))->render(route('certificates.verify', $certificate)));
            $certificate->update(['qr_code_path' => $path]);
        }
        if (! app()->environment('testing') && ! $certificate->pdf_path) {
            app(CertificatePdfService::class)->save($certificate);
        }

        return $certificate->refresh();
    }
}

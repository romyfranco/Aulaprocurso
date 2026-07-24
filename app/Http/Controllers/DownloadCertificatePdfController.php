<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificatePdfService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadCertificatePdfController extends Controller
{
    public function __invoke(Certificate $certificate, CertificatePdfService $pdf): StreamedResponse
    {
        Gate::authorize('view', $certificate);

        $disk = Storage::disk('public');
        $path = $certificate->pdf_path;

        if (blank($path) || ! $disk->exists($path)) {
            $path = $pdf->save($certificate);
        }

        return $disk->download(
            $path,
            "certificado-{$certificate->certificate_code}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }
}

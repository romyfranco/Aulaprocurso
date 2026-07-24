<?php

namespace App\Services;

use App\Models\Certificate;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    public function render(Certificate $certificate): string
    {
        $certificate->loadMissing('student', 'course');

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('certificates.pdf', [
            'certificate' => $certificate,
            'qrCodeDataUri' => $this->qrCodeDataUri($certificate),
        ])->render(), 'UTF-8');
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    public function save(Certificate $certificate): string
    {
        $relativePath = "certificates/{$certificate->certificate_code}.pdf";

        Storage::disk('public')->put($relativePath, $this->render($certificate));
        $certificate->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }

    private function qrCodeDataUri(Certificate $certificate): ?string
    {
        if (blank($certificate->qr_code_path)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($certificate->qr_code_path)) {
            return null;
        }

        return 'data:image/svg+xml;base64,'.base64_encode($disk->get($certificate->qr_code_path));
    }
}

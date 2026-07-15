<?php

namespace App\Jobs;

use App\Models\Certificate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\LaravelPdf\Facades\Pdf;

class GenerateCertificatePdf implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Certificate $certificate) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->certificate->loadMissing('student', 'course');
        $relativePath = "certificates/{$this->certificate->certificate_code}.pdf";
        $absolutePath = storage_path('app/public/'.$relativePath);
        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }
        Pdf::view('certificates.pdf', ['certificate' => $this->certificate])->format('a4')->landscape()->save($absolutePath);
        $this->certificate->update(['pdf_path' => $relativePath]);
    }
}

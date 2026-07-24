<?php

namespace App\Jobs;

use App\Models\Certificate;
use App\Services\CertificatePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(CertificatePdfService $pdf): void
    {
        $pdf->save($this->certificate);
    }
}

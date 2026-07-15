<?php

namespace App\Http\Controllers;

use App\Models\Certificate;

class CertificateVerificationController extends Controller
{
    public function __invoke(Certificate $certificate)
    {
        return view('certificates.verify', ['certificate' => $certificate->load('student', 'course')]);
    }
}

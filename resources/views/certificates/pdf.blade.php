<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: "DejaVu Sans", sans-serif; color: #172033; background: #172033; }
        .page { position: relative; width: 296mm; height: 209mm; background: #172033; overflow: hidden; }
        .accent { position: absolute; top: 0; right: 0; width: 104mm; height: 209mm; background: #6757f5; }
        .inner { position: absolute; top: 10mm; left: 10mm; width: 236mm; height: 155mm; padding: 17mm 20mm; background: #fff; border: 2px solid #d9d4ff; text-align: center; }
        .eyebrow { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; color: #6757f5; font-weight: bold; }
        .title { margin: 8mm 0 4mm; font-size: 34px; font-weight: bold; }
        .copy { font-size: 14px; color: #697386; }
        .student { margin: 8mm 0 2mm; font-size: 29px; font-weight: bold; }
        .course { margin-top: 3mm; font-size: 20px; color: #6757f5; font-weight: bold; }
        .meta { width: 62%; margin: 10mm auto 0; border-collapse: collapse; color: #697386; font-size: 11px; }
        .meta td { width: 50%; padding: 2mm; border-top: 1px solid #e5e7ef; }
        .footer { width: 100%; margin-top: 22mm; border-collapse: collapse; }
        .footer td { width: 33.333%; vertical-align: bottom; }
        .brand { text-align: left; font-size: 15px; font-weight: bold; color: #6757f5; }
        .qr-cell { text-align: right; }
        .qr { width: 25mm; height: 25mm; }
        .code { text-align: center; font-size: 8px; color: #9aa3b5; }
    </style>
</head>
<body>
    <div class="page">
        <div class="accent"></div>
        <div class="inner">
            <div class="eyebrow">Certificado de finalización</div>
            <div class="title">Reconocemos tu logro</div>
            <div class="copy">Se certifica que</div>
            <div class="student">{{ $certificate->student->name }}</div>
            <div class="copy">completó satisfactoriamente el curso</div>
            <div class="course">{{ $certificate->course->title }}</div>
            <table class="meta">
                <tr>
                    <td>Emitido: {{ $certificate->issued_at->format('d/m/Y') }}</td>
                    <td>Progreso: 100%</td>
                </tr>
            </table>
            <table class="footer">
                <tr>
                    <td class="brand">VoranaPro</td>
                    <td class="code">{{ $certificate->certificate_code }}</td>
                    <td class="qr-cell">
                        @if ($qrCodeDataUri)
                            <img class="qr" src="{{ $qrCodeDataUri }}" alt="Código QR">
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

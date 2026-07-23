@props(['topic'])

@php
    $topic->loadMissing(['revealPresentation', 'latestRevealUpload']);
    $presentation = $topic->revealPresentation;
    $latestUpload = $topic->latestRevealUpload;
    $isStaff = in_array(auth()->user()?->role, ['admin', 'instructor'], true);
    $launchUrl = route('topics.presentation.launch', $topic);
@endphp

<div style="display:grid;gap:1rem;width:100%">
    @if ($presentation?->isReady())
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <p style="font-weight:700;margin:0">{{ $presentation->original_name }}</p>
                <p style="color:#64748b;font-size:.875rem;margin:.25rem 0 0">
                    {{ number_format($presentation->file_count) }} archivos · {{ number_format($presentation->extracted_size / 1048576, 1) }} MB
                </p>
            </div>
            <a href="{{ $launchUrl }}" target="_blank" rel="noopener noreferrer"
               style="display:inline-flex;align-items:center;gap:.5rem;border-radius:.65rem;background:#2563eb;color:white;padding:.7rem 1rem;font-weight:700;text-decoration:none">
                Abrir en otra pestaña ↗
            </a>
        </div>

        <div style="width:100%;aspect-ratio:16/9;min-height:420px;max-height:75vh;overflow:hidden;border:1px solid #dbe3ef;border-radius:1rem;background:#0f172a">
            <iframe
                src="{{ $launchUrl }}"
                title="Presentación: {{ $topic->title }}"
                loading="lazy"
                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                allow="fullscreen"
                allowfullscreen
                referrerpolicy="no-referrer"
                style="width:100%;height:100%;border:0"
            ></iframe>
        </div>

        @if ($isStaff && $latestUpload?->status === 'processing' && $latestUpload->id !== $presentation->id)
            <p style="margin:0;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:.75rem;padding:.75rem 1rem">
                Hay una versión nueva procesándose. Esta presentación seguirá activa hasta que la nueva esté lista.
            </p>
        @elseif ($isStaff && $latestUpload?->status === 'failed')
            <p style="margin:0;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:.75rem;padding:.75rem 1rem">
                La última carga no pudo activarse: {{ $latestUpload->error_message }}
            </p>
        @endif
    @elseif ($latestUpload?->status === 'processing')
        <p style="margin:0;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe;border-radius:.75rem;padding:1rem">
            La presentación se está validando y estará disponible en unos minutos.
        </p>
    @elseif ($isStaff && $latestUpload?->status === 'failed')
        <p style="margin:0;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:.75rem;padding:1rem">
            No se pudo activar la presentación: {{ $latestUpload->error_message }}
        </p>
    @else
        <p style="margin:0;color:#64748b;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:.75rem;padding:1rem">
            Este tema todavía no tiene una presentación interactiva.
        </p>
    @endif
</div>

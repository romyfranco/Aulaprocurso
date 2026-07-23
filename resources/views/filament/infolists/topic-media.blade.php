@php
    $images = $topic->getMedia('images');
    $videos = $topic->getMedia('videos');
    $documents = $topic->getMedia('documents');
@endphp

<div style="display:grid;gap:1.5rem;width:100%">
    @if ($images->isNotEmpty())
        <section aria-label="Imágenes del tema">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 .75rem">Imágenes</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem">
                @foreach ($images as $image)
                    <a href="{{ $image->getUrl() }}" target="_blank" rel="noopener noreferrer"
                       style="display:block;overflow:hidden;border:1px solid #dbe3ef;border-radius:.9rem;background:#0f172a">
                        <img src="{{ $image->getUrl() }}" alt="{{ $image->name }}" loading="lazy"
                             style="display:block;width:100%;height:220px;object-fit:contain;background:#fff">
                        <span style="display:block;padding:.65rem .8rem;color:#cbd5e1;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $image->name }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($videos->isNotEmpty())
        <section aria-label="Videos del tema">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 .75rem">Videos</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem">
                @foreach ($videos as $video)
                    <figure style="margin:0;overflow:hidden;border:1px solid #dbe3ef;border-radius:.9rem;background:#0f172a">
                        <video controls preload="metadata" style="display:block;width:100%;max-height:420px">
                            <source src="{{ $video->getUrl() }}" type="{{ $video->mime_type }}">
                        </video>
                        <figcaption style="padding:.65rem .8rem;color:#cbd5e1;font-size:.8rem">{{ $video->name }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    @if ($documents->isNotEmpty())
        <section aria-label="Documentos del tema">
            <h3 style="font-size:1rem;font-weight:700;margin:0 0 .75rem">Documentos y presentaciones</h3>
            <div style="display:grid;gap:.65rem">
                @foreach ($documents as $document)
                    <a href="{{ $document->getUrl() }}" target="_blank" rel="noopener noreferrer"
                       style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 1rem;border:1px solid #dbe3ef;border-radius:.8rem;text-decoration:none">
                        <span style="min-width:0">
                            <strong style="display:block;color:inherit;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $document->name }}</strong>
                            <small style="color:#64748b">{{ strtoupper($document->extension) }} · {{ number_format($document->size / 1048576, 1) }} MB</small>
                        </span>
                        <span aria-hidden="true" style="font-weight:700;color:#2563eb">Abrir ↗</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

@php($presentation = $topic->getFirstMedia('presentation_pdf'))

@if ($presentation)
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;width:100%">
        <div style="min-width:0">
            <p style="margin:0;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $presentation->name }}</p>
            <p style="margin:.25rem 0 0;color:#64748b;font-size:.875rem">PDF · {{ number_format($presentation->size / 1048576, 1) }} MB</p>
        </div>
        <a
            href="{{ route('topics.pdf-presentation.view', $topic) }}"
            target="_blank"
            rel="noopener noreferrer"
            style="display:inline-flex;align-items:center;justify-content:center;gap:.5rem;min-height:46px;padding:.7rem 1.1rem;border-radius:.75rem;color:#fff;background:linear-gradient(135deg,#6757f5,#7968fa);box-shadow:0 12px 28px rgba(103,87,245,.24);font-weight:750;text-decoration:none"
        >
            Ver presentación <span aria-hidden="true">↗</span>
        </a>
    </div>
@endif

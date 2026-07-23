@php
    use App\Filament\Student\Resources\Topics\TopicResource;
    use App\Services\TopicAccessService;

    $enrollment->loadMissing('course.topics.quiz');
    $access = app(TopicAccessService::class);
@endphp

<div style="display:grid;gap:.75rem;width:100%">
    @forelse ($enrollment->course->topics as $index => $topic)
        @php $unlocked = $access->isUnlocked($enrollment, $topic); @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;border:1px solid {{ $unlocked ? '#dbe3ef' : '#e2e8f0' }};background:{{ $unlocked ? '#ffffff' : '#f8fafc' }};border-radius:.85rem;padding:1rem">
            <div style="display:flex;align-items:center;gap:.85rem;min-width:0">
                <span style="display:grid;place-items:center;flex:0 0 2rem;height:2rem;border-radius:999px;background:{{ $unlocked ? '#dbeafe' : '#e2e8f0' }};color:{{ $unlocked ? '#1d4ed8' : '#64748b' }};font-weight:800">
                    {{ $index + 1 }}
                </span>
                <div style="min-width:0">
                    <p style="font-weight:700;margin:0;color:#0f172a">{{ $topic->title }}</p>
                    <p style="margin:.2rem 0 0;color:#64748b;font-size:.875rem">{{ $unlocked ? $topic->description : 'Completa el tema anterior para desbloquearlo.' }}</p>
                </div>
            </div>

            @if ($unlocked)
                <a href="{{ TopicResource::getUrl('view', ['record' => $topic], panel: 'student') }}"
                   style="white-space:nowrap;border-radius:.6rem;background:#2563eb;color:white;padding:.55rem .85rem;font-weight:700;text-decoration:none">
                    Ver tema
                </a>
            @else
                <span aria-label="Tema bloqueado" style="color:#64748b;font-size:1.25rem">🔒</span>
            @endif
        </div>
    @empty
        <p style="margin:0;color:#64748b">Este curso todavía no tiene temas.</p>
    @endforelse
</div>

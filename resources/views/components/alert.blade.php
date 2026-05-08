@props([
    'type' => 'info',
    'message' => null,
    'messages' => [],
])

@php
    $visualType = $type === 'status' ? 'success' : $type;
    $visualType = in_array($visualType, ['success', 'error', 'warning', 'info', 'validation'], true)
        ? $visualType
        : 'info';

    $role = in_array($visualType, ['error', 'warning', 'validation'], true) ? 'alert' : 'status';

    $theme = [
        'success' => [
            'background' => '#f0fdf4',
            'border' => '#bbf7d0',
            'color' => '#166534',
            'shadow' => 'rgba(22, 163, 74, 0.08)',
        ],
        'error' => [
            'background' => '#fef2f2',
            'border' => '#fecaca',
            'color' => '#991b1b',
            'shadow' => 'rgba(220, 38, 38, 0.08)',
        ],
        'validation' => [
            'background' => '#fef2f2',
            'border' => '#fecaca',
            'color' => '#991b1b',
            'shadow' => 'rgba(220, 38, 38, 0.08)',
        ],
        'warning' => [
            'background' => '#fff7ed',
            'border' => '#fed7aa',
            'color' => '#9a3412',
            'shadow' => 'rgba(234, 88, 12, 0.08)',
        ],
        'info' => [
            'background' => '#eff6ff',
            'border' => '#bfdbfe',
            'color' => '#1d4ed8',
            'shadow' => 'rgba(37, 99, 235, 0.08)',
        ],
    ][$visualType];

    if ($messages instanceof \Illuminate\Support\MessageBag || $messages instanceof \Illuminate\Support\ViewErrorBag) {
        $messages = $messages->all();
    } elseif (is_string($messages)) {
        $messages = [$messages];
    } elseif (! is_array($messages)) {
        $messages = [];
    }

    $messages = array_values(array_filter($messages, fn ($item) => filled($item)));
    $hasMessages = count($messages) > 0;
@endphp

<div
    {{ $attributes->merge(['class' => "epp-alert epp-alert-{$visualType}"]) }}
    role="{{ $role }}"
    style="width: fit-content; max-width: min(100%, 760px); display: inline-flex; align-items: flex-start; gap: 10px; margin-bottom: 18px; padding: 10px 14px; border: 1px solid {{ $theme['border'] }}; border-radius: 12px; background: {{ $theme['background'] }}; color: {{ $theme['color'] }}; font-size: 14px; font-weight: 800; line-height: 1.35; box-shadow: 0 10px 20px {{ $theme['shadow'] }};"
>
    <span style="display: inline-flex; flex: 0 0 auto; margin-top: 1px;">
        @if($visualType === 'success')
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                <path d="M9 12l2 2 4-4" />
                <circle cx="12" cy="12" r="9" />
            </svg>
        @elseif($visualType === 'error' || $visualType === 'validation')
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v5" />
                <path d="M12 16h.01" />
            </svg>
        @elseif($visualType === 'warning')
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                <path d="m12 3 9 16H3L12 3Z" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
        @else
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 11v5" />
                <path d="M12 8h.01" />
            </svg>
        @endif
    </span>

    <div style="min-width: 0; overflow-wrap: anywhere;">
        @if($hasMessages)
            @if(count($messages) === 1)
                {{ $messages[0] }}
            @else
                <ul style="display: grid; gap: 4px; margin: 0; padding-left: 18px;">
                    @foreach($messages as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        @elseif($message)
            {{ $message }}
        @else
            {{ $slot }}
        @endif
    </div>
</div>

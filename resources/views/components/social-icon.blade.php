{{--
    Renders one social platform's glyph — closer approximations of each
    platform's actual mark than the first pass (not traced pixel-for-pixel
    from official brand artwork, but recognizable at a glance), all on a
    24x24 viewBox so they size consistently via the h-*/w-* classes passed
    in on $attributes. Single currentColor throughout so it follows
    k-navlink's hover state and dark mode automatically.
--}}
@props(['icon'])

@switch($icon)
    @case('facebook')
        <svg viewBox="0 0 24 24" fill="none" {{ $attributes }}>
            <circle cx="12" cy="12" r="9.25" stroke="currentColor" stroke-width="1.5"/>
            <path fill="currentColor" d="M13.6 21v-7.3h2.2l.3-2.7h-2.5V9.3c0-.8.2-1.3 1.3-1.3h1.4V5.6c-.2 0-1.1-.1-2.1-.1-2.1 0-3.5 1.3-3.5 3.6v2h-2.4v2.7h2.4V21h2.9Z"/>
        </svg>
        @break

    @case('instagram')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" {{ $attributes }}>
            <rect x="3.25" y="3.25" width="17.5" height="17.5" rx="5.5"/>
            <circle cx="12" cy="12" r="4.4"/>
            <circle cx="17.1" cy="6.9" r="0.9" fill="currentColor" stroke="none"/>
        </svg>
        @break

    @case('threads')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
            <path d="M12.2 2.8c-5.1 0-8.4 3.3-8.4 9.2s3.3 9.2 8.4 9.2c4.3 0 7.2-2.1 7.2-5.6 0-2.6-1.6-4.4-4.6-4.4-2.4 0-4.1 1.2-4.1 3.1 0 1.4 1.1 2.3 2.5 2.3 1.6 0 2.7-1 3-2.6"/>
            <path d="M8.6 9.4c1-.9 2.3-1.4 3.8-1.4 3 0 5 1.7 5.2 4.5"/>
        </svg>
        @break

    @case('tiktok')
        <svg viewBox="0 0 24 24" fill="currentColor" {{ $attributes }}>
            <path d="M16.6 2h-3v13.2a2.9 2.9 0 1 1-2.1-2.8v-3.1a6 6 0 1 0 5.1 5.9V9.3c1.1.8 2.4 1.2 3.9 1.2V7.5c-2.1 0-3.9-1.6-3.9-3.7V2Z"/>
        </svg>
        @break

    @case('x')
        <svg viewBox="0 0 24 24" fill="currentColor" {{ $attributes }}>
            <path d="M13.5 10.8 20 3h-2.4l-5.6 6.4L7.6 3H2.8l6.8 9.9L2.8 21h2.4l5.9-6.8L15.9 21h4.8l-7.2-10.2Zm-2.1 2.4-.7-1L5.3 4.7h2l4.4 6.3.7 1 5.7 8.2h-2l-4.7-6.8Z"/>
        </svg>
        @break

    @case('youtube')
        <svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd" {{ $attributes }}>
            <path d="M21.6 7.6a3 3 0 0 0-2.1-2.1C17.8 5 12 5 12 5s-5.8 0-7.5.5a3 3 0 0 0-2.1 2.1C2 9.3 2 12 2 12s0 2.7.4 4.4a3 3 0 0 0 2.1 2.1C6.2 19 12 19 12 19s5.8 0 7.5-.5a3 3 0 0 0 2.1-2.1c.4-1.7.4-4.4.4-4.4s0-2.7-.4-4.4ZM10 15.5v-7l6 3.5-6 3.5Z"/>
        </svg>
        @break

    @case('linkedin')
        <svg viewBox="0 0 24 24" fill="currentColor" {{ $attributes }}>
            <rect x="3" y="3" width="18" height="18" rx="3"/>
            <path fill="var(--color-surface, #fff)" d="M7.8 9.6h2.6V18H7.8V9.6Zm1.3-4.2a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12.3 9.6h2.5v1.15h.04c.35-.66 1.2-1.36 2.47-1.36 2.64 0 3.13 1.74 3.13 4V18h-2.6v-4.15c0-.99-.02-2.26-1.38-2.26-1.38 0-1.59 1.08-1.59 2.19V18h-2.57V9.6Z"/>
        </svg>
        @break
@endswitch

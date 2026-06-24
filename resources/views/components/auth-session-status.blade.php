@props(['status'])

@if ($status)
    @php
        $message = match ($status) {
            'verification-link-sent' => __('A new verification link has been sent to the email address you provided during registration.'),
            'profile-updated' => __('Saved.'),
            'password-updated' => __('Saved.'),
            default => __($status),
        };
    @endphp

    <div
        {{ $attributes->except('class') }}
        class="sr-only"
        x-data
        x-init="window.App.toast({ type: 'success', message: @js($message) })"
    >
        {{ $message }}
    </div>
@endif

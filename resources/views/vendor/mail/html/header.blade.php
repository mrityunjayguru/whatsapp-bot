@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php
    $logoPath = public_path('build/images/logo.png');
    $logoBase64 = null;
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }
@endphp
@if ($logoBase64)
    <img src="{{ $logoBase64 }}" alt="{{ config('app.name', 'Chatbot') }}" style="max-height: 48px; max-width: 180px; height: auto; width: auto; object-fit: contain; display: block; margin: 0 auto; border: 0;">
@else
    <span style="font-size: 20px; font-weight: bold; color: #18181b;">{{ config('app.name', 'Chatbot') }}</span>
@endif
</a>
</td>
</tr>

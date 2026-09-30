@php
  // Same colour tokens Contacts uses for its status badges.
  $tokens = match ($status) {
    'new' => ['bg' => '#eff6ff', 'fg' => '#1d4ed8', 'border' => '#bfdbfe', 'dot' => '#3b82f6'],
    'reviewed' => ['bg' => '#fefce8', 'fg' => '#a16207', 'border' => '#fde68a', 'dot' => '#eab308'],
    'converted', 'published' => ['bg' => '#f0fdf4', 'fg' => '#15803d', 'border' => '#bbf7d0', 'dot' => '#22c55e'],
    default => ['bg' => '#f4f4f5', 'fg' => '#52525b', 'border' => '#e4e4e7', 'dot' => '#a1a1aa'],
  };
@endphp
<span class="cf-badge" style="background: {{ $tokens['bg'] }}; color: {{ $tokens['fg'] }}; border-color: {{ $tokens['border'] }};">
  <span class="cf-dot" style="background: {{ $tokens['dot'] }};"></span>{{ $label }}
</span>

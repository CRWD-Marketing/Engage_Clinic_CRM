@php
  // Same badge shape as cms_forms.partials.status-badge, with vendor and
  // document states mapped onto its colour tokens.
  $tokens = match ($status) {
    'under_review', 'pending' => ['bg' => '#eff6ff', 'fg' => '#1d4ed8', 'border' => '#bfdbfe', 'dot' => '#3b82f6'],
    'active', 'valid', 'complete' => ['bg' => '#f0fdf4', 'fg' => '#15803d', 'border' => '#bbf7d0', 'dot' => '#22c55e'],
    'on_hold', 'expiring' => ['bg' => '#fefce8', 'fg' => '#a16207', 'border' => '#fde68a', 'dot' => '#eab308'],
    'suspended', 'expired' => ['bg' => '#fef2f2', 'fg' => '#b91c1c', 'border' => '#fecaca', 'dot' => '#ef4444'],
    'critical' => ['bg' => '#FCEAF0', 'fg' => '#A82348', 'border' => '#F6C9D6', 'dot' => '#C8355F'],
    default => ['bg' => '#f4f4f5', 'fg' => '#52525b', 'border' => '#e4e4e7', 'dot' => '#a1a1aa'],
  };
@endphp
<span class="cf-badge" style="background: {{ $tokens['bg'] }}; color: {{ $tokens['fg'] }}; border-color: {{ $tokens['border'] }};">
  <span class="cf-dot" style="background: {{ $tokens['dot'] }};"></span>{{ $label }}
</span>

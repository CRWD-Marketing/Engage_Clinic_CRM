@php
  // Initials on a colour picked from the name, so the same client always
  // gets the same avatar. No name -> a neutral person icon.
  $name = trim((string) ($name ?? ''));
  $size = $size ?? 34;
  $initials = collect(preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $palette = [['#FCEAF0', '#A82348'], ['#EAF1F8', '#16436E'], ['#ecfdf5', '#047857'], ['#fef3c7', '#92400e'], ['#ede9fe', '#5b21b6'], ['#e0f2fe', '#0369a1'], ['#ffedd5', '#c2410c']];
  [$bg, $fg] = $initials === '' ? ['#f4f4f5', '#a1a1aa'] : $palette[crc32(mb_strtolower($name)) % count($palette)];
@endphp
<span class="cf-avatar" aria-hidden="true" style="width:{{ $size }}px;height:{{ $size }}px;background:{{ $bg }};color:{{ $fg }};font-size:{{ round($size * .38) }}px">
  @if ($initials === '')<i class="fas fa-user"></i>@else{{ $initials }}@endif
</span>

@extends('layouts.landing')

@section('title', 'Engage Clinic · Blog')

@php
  $activePage = 'blog';
@endphp

@section('content')

<!-- Hero -->
<section class="js-hero py-20 md:py-28 text-center" style="background:var(--navy-deep);">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <span class="eyebrow-plain" style="color:var(--pink-mid);">Blog</span>
    <h1 class="display mt-4 text-3xl sm:text-5xl font-extrabold leading-[1.15] text-white">Resources for families and clinicians</h1>
    <p class="mt-5 text-white/75 text-base sm:text-lg leading-relaxed">Practical guides, behavioral science insights, and family stories from the Engage team.</p>
  </div>
</section>

@php
  $tagAccent = [
    'indigo' => ['soft' => '#EEEEFC', 'color' => 'var(--indigo)'],
    'pink' => ['soft' => 'var(--pink-soft)', 'color' => 'var(--pink)'],
    'green' => ['soft' => '#E6F7EC', 'color' => 'var(--green)'],
    'orange' => ['soft' => '#FDECDD', 'color' => 'var(--orange)'],
  ];

  $featured = [
    'image' => asset('landingpage_assets/Early_Intervention.png'),
    'tag' => 'ABA Therapy · Featured', 'accent' => 'indigo',
    'title' => 'What is ABA Therapy? A Complete Guide for UAE Families',
    'excerpt' => 'Applied Behavior Analysis demystified — what it is, how sessions work, and what to expect in your first month.',
    'time' => '6 min', 'date' => 'June 12, 2025',
  ];

  $posts = [
    [
      'image' => asset('landingpage_assets/ABA_Intervention.png'),
      'tag' => 'Early Intervention', 'accent' => 'pink',
      'title' => 'The Power of Early Intervention: Why Ages 2–6 Matter Most',
      'excerpt' => 'Neuroscience explains why the earliest years offer the greatest window for meaningful developmental change.',
      'time' => '5 min', 'date' => 'May 28, 2025',
    ],
    [
      'image' => 'https://images.unsplash.com/photo-1585240975858-7264fd020798?fm=jpg&q=80&w=700&auto=format&fit=crop',
      'tag' => 'Parent Guides', 'accent' => 'green',
      'title' => '10 Strategies to Reinforce ABA Progress at Home',
      'excerpt' => 'How parents can extend gains from therapy sessions into daily routines — mealtimes, bedtime, and play.',
      'time' => '8 min', 'date' => 'May 10, 2025',
    ],
    [
      'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?fm=jpg&q=80&w=700&auto=format&fit=crop',
      'tag' => 'Behavioral Science', 'accent' => 'orange',
      'title' => 'Understanding Functional Behavioral Assessments (FBA)',
      'excerpt' => 'What happens in an FBA, why it matters, and how it shapes your child\'s entire treatment plan.',
      'time' => '7 min', 'date' => 'April 22, 2025',
    ],
    [
      'image' => 'https://images.unsplash.com/photo-1758270704925-fa59d93119c1?fm=jpg&q=80&w=700&auto=format&fit=crop',
      'tag' => 'UAE & Culture', 'accent' => 'indigo',
      'title' => 'Culturally Sensitive ABA: Therapy That Honors Your Family',
      'excerpt' => 'How Engage designs programs with cultural awareness at the center — honoring family values, language, and traditions.',
      'time' => '5 min', 'date' => 'April 5, 2025',
    ],
    [
      'image' => 'https://images.unsplash.com/photo-1638829154930-9b47ff9cb533?fm=jpg&q=80&w=700&auto=format&fit=crop',
      'tag' => 'Camps & Events', 'accent' => 'pink',
      'title' => 'Summer Adventure Camp 2026: What to Expect at Fun City',
      'excerpt' => 'Everything about our July–August camp at Abu Dhabi Mall — themes, skills, and how to book.',
      'time' => '4 min', 'date' => 'March 18, 2025',
    ],
  ];

  $clockIcon = '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>';
@endphp

<!-- Featured post -->
<section class="py-16 md:py-24 dotted">
  <div class="max-w-6xl mx-auto px-6 lg:px-8">
    <a href="#" class="card blog-card grid grid-cols-1 lg:grid-cols-2 overflow-hidden">
      <div class="aspect-[16/10] lg:aspect-auto">
        <img src="{{ $featured['image'] }}" alt="{{ $featured['title'] }}" class="w-full h-full object-cover">
      </div>
      <div class="p-8 lg:p-10 flex flex-col justify-center">
        @php $fa = $tagAccent[$featured['accent']]; @endphp
        <span class="reveal-text service-badge w-fit" style="background:{{ $fa['soft'] }};color:{{ $fa['color'] }};">{{ $featured['tag'] }}</span>
        <h2 class="reveal-heading display mt-4 text-2xl sm:text-3xl font-extrabold text-[var(--navy)] leading-tight">{{ $featured['title'] }}</h2>
        <p class="reveal-text mt-3 text-[var(--text-secondary)] leading-relaxed">{{ $featured['excerpt'] }}</p>
        <div class="reveal-text flex items-center gap-2 mt-6 text-xs text-[var(--text-muted)]">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $clockIcon !!}</svg>
          {{ $featured['time'] }} <span>·</span> {{ $featured['date'] }}
        </div>
      </div>
    </a>
  </div>
</section>

<!-- Post grid -->
<section class="pb-16 md:pb-24 dotted">
  <div class="max-w-6xl mx-auto px-6 lg:px-8">
    <div class="reveal-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
      @foreach ($posts as $post)
        @php $pa = $tagAccent[$post['accent']]; @endphp
        <a href="#" class="card blog-card overflow-hidden block">
          <div class="aspect-[16/10]">
            <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="w-full h-full object-cover">
          </div>
          <div class="p-6">
            <span class="service-badge w-fit" style="background:{{ $pa['soft'] }};color:{{ $pa['color'] }};">{{ $post['tag'] }}</span>
            <h3 class="font-bold text-[var(--navy)] mt-3.5 leading-snug">{{ $post['title'] }}</h3>
            <p class="text-[var(--text-secondary)] text-sm mt-2 leading-relaxed">{{ $post['excerpt'] }}</p>
            <div class="flex items-center gap-2 mt-5 text-xs text-[var(--text-muted)]">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $clockIcon !!}</svg>
              {{ $post['time'] }} <span>·</span> {{ $post['date'] }}
            </div>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>

@include('landing_page.partials.booking-modal')

@endsection

@push('head')
<style>
  .blog-card{transition:transform .3s cubic-bezier(.22,.61,.36,1), box-shadow .3s cubic-bezier(.22,.61,.36,1), border-color .3s ease;}
  .blog-card:hover{transform:translateY(-6px);box-shadow:0 20px 40px -16px rgba(14,46,76,.25);border-color:var(--pink-border);}
  .blog-card img{transition:transform .4s ease;}
  .blog-card:hover img{transform:scale(1.04);}
  @media (prefers-reduced-motion: reduce){
    .blog-card, .blog-card img{transition:none;}
    .blog-card:hover{transform:none;}
    .blog-card:hover img{transform:none;}
  }
</style>
@endpush


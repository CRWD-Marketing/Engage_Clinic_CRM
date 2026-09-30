@extends('layouts.landing')

@section('title', 'Engage Clinic · Our Services')

@php
  $activePage = 'services';
@endphp

@section('content')

<!-- Hero -->
<section class="js-hero relative py-24 md:py-32 overflow-hidden" style="background:var(--navy-deep);">
  <img src="https://images.unsplash.com/photo-1587323655395-b1c77a12c89a?fm=jpg&q=80&w=1600&auto=format&fit=crop" alt="Child happily engaged in a therapy session" class="absolute inset-0 w-full h-full object-cover opacity-40">
  <div class="absolute inset-0" style="background:linear-gradient(90deg,var(--navy-deep) 20%,rgba(14,46,76,.55) 60%,rgba(14,46,76,.25) 100%);"></div>
  <div class="max-w-[1440px] mx-auto px-6 lg:px-8 relative">
    <div class="max-w-xl">
      <span class="eyebrow-plain" style="color:var(--pink-mid);">Our Programs</span>
      <h1 class="display mt-4 text-4xl sm:text-5xl font-extrabold leading-[1.12] text-white">Every child deserves a program built for them.</h1>
      <p class="mt-5 text-white/80 text-base sm:text-lg leading-relaxed">Evidence-based services spanning early childhood through adolescence — delivered in-home, in-community, and always in partnership with family. Health insurance accepted.</p>
      <div class="flex flex-wrap gap-2.5 mt-8">
        <span class="trust-pill on-dark"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Globally Accredited</span>
        <span class="trust-pill on-dark"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> BCBA Certified</span>
        <span class="trust-pill on-dark"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Insurance Accepted</span>
        <span class="trust-pill on-dark"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Home-Based</span>
      </div>
    </div>
  </div>
</section>

@php
  $programs = [
    [
      'badge' => 'Core Program', 'accent' => 'indigo', 'image' => asset('landingpage_assets/ABA_Intervention.png'),
      'title' => 'ABA Intervention Therapy',
      'desc' => 'Applied Behavior Analysis is the gold standard in autism and developmental therapy. Our individualized ABA programs are built around each child\'s strengths, targeting developmental needs in close partnership with parents.',
      'checks' => ['Individualized treatment plans', 'Regular progress assessments', 'Evidence-based methods', 'Strength-based approach', 'Parent collaboration built in', 'Home & community delivery'],
      'icon' => 'info', 'imgSide' => 'right',
    ],
    [
      'badge' => 'Ages 2–6', 'accent' => 'pink', 'image' => asset('landingpage_assets/Early_Intervention.png'),
      'title' => 'Intensive Early Intervention',
      'desc' => 'Early intervention is the most powerful window for development. Our intensive programs help young children acquire crucial skills and hit developmental milestones — setting them up for lasting school readiness.',
      'checks' => ['Developmental milestone targeting', 'Social-emotional learning', 'Daily living skills', 'School readiness skills', 'Communication development', 'Play-based therapy'],
      'icon' => 'sparkle', 'imgSide' => 'left',
    ],
    [
      'badge' => 'Ages 6–18', 'accent' => 'green', 'image' => asset('landingpage_assets/School_Age_Support.png'),
      'title' => 'School-Age Intervention',
      'desc' => 'Our school-age programs provide customized educational support that addresses individual learning challenges, behavior in school settings, and the complex social world of older childhood.',
      'checks' => ['School-setting behavior support', 'Social skills coaching', 'Collaboration with educators', 'Academic skill building', 'Peer relationship guidance', 'Transition planning'],
      'icon' => 'cap', 'imgSide' => 'right',
    ],
    [
      'badge' => 'Family Support', 'accent' => 'orange', 'image' => asset('landingpage_assets/Parent_training.png'),
      'title' => 'Personalized Parent Trainings',
      'desc' => 'Parent training is woven into every treatment plan — giving families the tools, feedback, and confidence to be active participants in their child\'s growth.',
      'checks' => ['Integrated into all programs', 'Behavior management strategies', 'Home routine guidance', 'Hands-on skill coaching', 'Progress feedback sessions', 'Cultural sensitivity'],
      'icon' => 'heart', 'imgSide' => 'left',
    ],
    [
      'badge' => 'Communication', 'accent' => 'pink', 'image' => asset('landingpage_assets/Speech_therapy.png'),
      'title' => 'Speech Therapy',
      'desc' => 'Our speech-language therapy supports children in developing functional communication — from early language acquisition to articulation, social communication, and AAC for non-verbal learners.',
      'checks' => ['Expressive & receptive language', 'Social communication skills', 'Early language acquisition', 'Articulation & phonology', 'AAC device support', 'Collaboration with ABA team'],
      'icon' => 'chat', 'imgSide' => 'right',
    ],
    [
      'badge' => 'Assessment', 'accent' => 'indigo', 'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?fm=jpg&q=80&w=900&auto=format&fit=crop',
      'title' => 'Assessment in Behavior Analysis',
      'desc' => 'We conduct thorough Functional Behavioral Assessments (FBA) to identify root causes of challenging behaviors through parent interviews, staff interviews, and direct observation.',
      'checks' => ['Functional Behavioral Assessment', 'Direct observation', 'Treatment recommendations', 'Parent & staff interviews', 'Behavior function identification', 'Written assessment reports'],
      'icon' => 'shield', 'imgSide' => 'left',
    ],
  ];
  $accentMap = [
    'indigo' => ['bg' => 'var(--indigo)', 'soft' => '#EEEEFC', 'btn' => 'var(--indigo)'],
    'pink' => ['bg' => 'var(--pink)', 'soft' => 'var(--pink-soft)', 'btn' => 'var(--pink)'],
    'green' => ['bg' => 'var(--green)', 'soft' => '#E6F7EC', 'btn' => 'var(--green)'],
    'orange' => ['bg' => 'var(--orange)', 'soft' => '#FDECDD', 'btn' => 'var(--orange)'],
  ];
  $icons = [
    'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    'sparkle' => '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>',
    'cap' => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.5 3 3 6 3s6-1.5 6-3v-5"/>',
    'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>',
    'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  ];
@endphp

@foreach ($programs as $i => $p)
  @php $a = $accentMap[$p['accent']]; @endphp
  <section class="py-16 md:py-20 {{ $i % 2 === 0 ? 'dotted' : 'bg-white border-y border-[var(--border)]' }}">
    <div class="max-w-[1440px] mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

      <div class="{{ $p['imgSide'] === 'left' ? 'lg:order-1' : 'lg:order-2' }}">
        <div class="card photo-wrap rounded-[26px] overflow-hidden aspect-[4/3]">
          <img src="{{ $p['image'] }}" alt="{{ $p['title'] }}" class="w-full h-full object-cover">
        </div>
      </div>

      <div class="{{ $p['imgSide'] === 'left' ? 'lg:order-2' : 'lg:order-1' }}">
        <span class="reveal-text service-badge" style="background:{{ $a['soft'] }};color:{{ $a['btn'] }};">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$p['icon']] !!}</svg>
          {{ $p['badge'] }}
        </span>
        <h2 class="reveal-heading display mt-4 text-2xl sm:text-3xl font-extrabold text-[var(--navy)]">{{ $p['title'] }}</h2>
        <p class="reveal-text mt-3 text-[var(--text-secondary)] leading-relaxed">{{ $p['desc'] }}</p>

        <div class="reveal-group grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5 mt-6">
          @foreach ($p['checks'] as $check)
            <div class="service-check">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $a['btn'] }}" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
              {{ $check }}
            </div>
          @endforeach
        </div>

        <a href="{{ route('contact') }}" class="mt-7 inline-flex items-center gap-2 text-white font-bold text-sm rounded-full px-6 py-3" style="background:{{ $a['btn'] }};">
          Enquire About This Program
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M9 18l6-6-6-6"/></svg>
        </a>
      </div>

    </div>
  </section>
@endforeach

<!-- CTA -->
<section class="py-16 md:py-20 bg-white">
  <div class="max-w-2xl mx-auto px-6 lg:px-8 text-center">
    <span class="reveal-text trust-pill" style="border-color:var(--pink-border);color:var(--pink);">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
      Health Insurance Accepted · Free 30-Min Consultation Available
    </span>
    <h2 class="reveal-heading display mt-6 text-2xl sm:text-3xl font-extrabold text-[var(--navy)]">Not sure which program is right?</h2>
    <p class="reveal-text mt-3 text-[var(--text-secondary)]">Book a free 30-minute consultation. Our clinicians will guide you to the right starting point.</p>
    <a href="#" onclick="event.preventDefault(); openBookingModal();" class="btn-pink inline-flex mt-7">Book Free Consultation →</a>
  </div>
</section>

@include('landing_page.partials.booking-modal')

@endsection


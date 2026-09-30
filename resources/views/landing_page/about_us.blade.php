@extends('layouts.landing')

@section('title', 'Engage Clinic · About Us')

@php
  $activePage = 'about';
@endphp

@section('content')

<!-- Hero -->
<section class="js-hero py-20 md:py-28 text-center" style="background:var(--navy-deep);">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <span class="eyebrow-plain" style="color:var(--pink-mid);">About Us</span>
    <h1 class="display mt-4 text-3xl sm:text-5xl font-extrabold leading-[1.15] text-white">The minds and hearts behind Engage</h1>
    <p class="mt-5 text-white/75 text-base sm:text-lg leading-relaxed">Founded with a deep belief that every child deserves world-class behavioral support — regardless of where they live or how they learn.</p>
  </div>
</section>

<!-- Mission / Vision -->
<section class="py-16 md:py-24 dotted">
  <div class="max-w-6xl mx-auto px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card p-8">
      <span class="eyebrow-plain reveal-text">Mission</span>
      <h3 class="reveal-heading display mt-3 text-2xl font-extrabold text-[var(--navy)]">We empower children and families to thrive.</h3>
      <p class="reveal-text mt-4 text-[var(--text-secondary)] leading-relaxed">By delivering flexible, evidence-based therapy that promotes real-world growth and inclusion — meeting families where they are, in their homes, in their communities.</p>
    </div>
    <div class="p-8 rounded-[20px]" style="background:var(--navy-deep);">
      <span class="eyebrow-plain reveal-text" style="color:var(--pink-mid);">Vision</span>
      <h3 class="reveal-heading display mt-3 text-2xl font-extrabold text-white">Transforming behavioral therapy in the UAE.</h3>
      <p class="reveal-text mt-4 text-white/70 leading-relaxed">Through connection, compassion, and clinical excellence — raising the standard for what families can expect from behavioral health care across the region.</p>
    </div>
  </div>
</section>

<!-- Founder -->
<section class="py-16 md:py-24 bg-white border-y border-[var(--border)]">
  <div class="max-w-6xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
    <div>
      <img src="{{ asset('about_us/founder.png') }}" alt="Hong Chun Tan, Founder & Clinical Director of Engage Clinic" class="w-full h-auto rounded-[26px] border border-[var(--border)] shadow-sm">
    </div>
    <div>
      <span class="eyebrow-plain reveal-text">Founder &amp; Clinical Director</span>
      <h2 class="reveal-heading display mt-3 text-3xl sm:text-4xl font-extrabold text-[var(--navy)]">Hong Chun Tan</h2>
      <p class="reveal-text mt-2 text-sm text-[var(--text-muted)] font-semibold">BCBA · QBA · MCBA · LBA-Dubai</p>
      <p class="reveal-text mt-5 text-[var(--text-secondary)] leading-relaxed">With decades of experience in behavioral science across multiple countries, Hong Chun Tan founded Engage to address a critical gap: world-class ABA therapy that extends into the home, not just the clinic.</p>
      <p class="reveal-text mt-4 text-[var(--text-secondary)] leading-relaxed">Previously serving as Founding President of the Malaysia Association of Behaviour Analysis, he brings clinical rigor and deep cultural sensitivity to every program he designs.</p>
      <div class="reveal-group flex flex-wrap gap-2.5 mt-7">
        <span class="service-badge" style="background:var(--pink-soft);color:var(--pink);">BCBA Certified</span>
        <span class="service-badge" style="background:var(--pink-soft);color:var(--pink);">Founding President — Malaysia ABA</span>
        <span class="service-badge" style="background:var(--pink-soft);color:var(--pink);">Global Accreditation</span>
        <span class="service-badge" style="background:var(--pink-soft);color:var(--pink);">LBA Dubai Licensed</span>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-16 md:py-20" style="background:var(--pink);">
  <div class="max-w-2xl mx-auto px-6 lg:px-8 text-center">
    <h2 class="reveal-heading display text-3xl md:text-4xl font-extrabold text-white">Join our growing family of thriving children</h2>
    <p class="reveal-text text-white/85 mt-4 max-w-md mx-auto">Every journey begins with a conversation. Reach out today and discover how Engage can support your child.</p>
    <a href="{{ route('contact') }}" class="btn-white inline-flex mt-8">Get in Touch →</a>
  </div>
</section>

@include('landing_page.partials.booking-modal')

@endsection


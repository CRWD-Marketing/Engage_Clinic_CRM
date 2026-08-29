@extends('layouts.landing')

@section('title', 'Engage Clinic · Therapy, reimagined')

@php
  $activePage = 'home';
@endphp

@section('content')

<!-- Hero -->
<section id="home" class="js-hero dotted relative py-12 md:py-16 overflow-hidden">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-[1fr_1.15fr] gap-10 items-center">

    <!-- Left: copy -->
    <div>
      <span class="eyebrow" id="heroEyebrow">✦ UAE's Pioneer ABA Therapy Center</span>
      <h1 class="display mt-6 text-[2.25rem] sm:text-5xl lg:text-[3.4rem] font-extrabold leading-[1.12] text-[var(--navy)]" id="heroHeading">
        Every child deserves therapy built around their strengths.
      </h1>
      <p class="mt-5 text-[var(--text-secondary)] text-base sm:text-lg max-w-md leading-relaxed" id="heroParagraph">
        Individualised ABA programs delivered in-home across Abu Dhabi, grounded in evidence and family partnership.
      </p>

      <div class="flex flex-wrap items-center gap-3 mt-8">
        <a href="#" id="heroBookBtn" onclick="event.preventDefault(); openBookingModal();" class="btn-pink">Book Free Consultation →</a>
        <a href="#programmes" class="btn-ghost">Explore Services</a>
      </div>

      <div class="flex flex-wrap gap-2.5 mt-7">
        <span class="trust-pill"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Globally Accredited</span>
        <span class="trust-pill"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> BCBA Certified</span>
        <span class="trust-pill"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Insurance Accepted</span>
        <span class="trust-pill"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Home-Based</span>
      </div>

      <div class="flex items-center gap-3 mt-9">
        <button class="carousel-btn" onclick="goToHeroSlide(heroIndex - 1)" aria-label="Previous">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <div class="flex items-center gap-1.5" id="galleryDots">
          <button class="carousel-dot active" onclick="goToHeroSlide(0)" aria-label="ABA Intervention"></button>
          <button class="carousel-dot" onclick="goToHeroSlide(1)" aria-label="Early Intervention"></button>
          <button class="carousel-dot" onclick="goToHeroSlide(2)" aria-label="School-Age Support"></button>
          <button class="carousel-dot" onclick="goToHeroSlide(3)" aria-label="Speech Therapy"></button>
        </div>
        <button class="carousel-btn" onclick="goToHeroSlide(heroIndex + 1)" aria-label="Next">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
        </button>
      </div>
    </div>

    <!-- Right: gallery -->
    <div class="hero-gallery" id="heroGallery">
      <div class="hero-slide-item is-active"
           data-heading="Every child deserves therapy built around their strengths."
           data-paragraph="Individualised ABA programs delivered in-home across Abu Dhabi, grounded in evidence and family partnership."
           data-accent="#C8355F" data-accent-soft="#FCEAF0" data-accent-border="#F6C9D6">
        <img src="{{ asset('landingpage_assets/ABA_Intervention.png') }}" alt="Child happily engaged with an Engage Clinic therapist during an ABA session">
        <span class="scrim"></span>
        <span class="item-badge" style="background:var(--pink);">Core Programme</span>
        <div class="main-content">
          <h4 class="display">ABA Intervention</h4>
          <p>Individualised ABA programs delivered in-home across Abu Dhabi, grounded in evidence and family partnership.</p>
          <a href="#programmes">Learn more <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M9 18l6-6-6-6"/></svg></a>
        </div>
        <span class="strip-label">ABA Intervention</span>
      </div>

      <div class="hero-slide-item"
           data-heading="The earliest years hold the greatest power to change a life."
           data-paragraph="Intensive early intervention programs that hit developmental milestones and prepare children for school."
           data-accent="#5B5FE0" data-accent-soft="#EEEEFC" data-accent-border="#D6D6F8">
        <img src="{{ asset('landingpage_assets/Early_Intervention.png') }}" alt="Child happily engaged with an Engage Clinic therapist during an early intervention session">
        <span class="scrim"></span>
        <span class="item-badge" style="background:var(--indigo);">Ages 2 – 6</span>
        <div class="main-content">
          <h4 class="display">Early Intervention</h4>
          <p>Intensive early intervention programs that hit developmental milestones and prepare children for school.</p>
          <a href="#programmes">Learn more <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M9 18l6-6-6-6"/></svg></a>
        </div>
        <span class="strip-label">Early Intervention</span>
      </div>

      <div class="hero-slide-item"
           data-heading="Growing up is complex. We help every step of the way."
           data-paragraph="Customised school-age intervention addressing behaviour, social skills, and academic challenges."
           data-accent="#1E8A4C" data-accent-soft="#E6F7EC" data-accent-border="#BEE7CC">
        <img src="{{ asset('landingpage_assets/School_Age_Support.png') }}" alt="Children happily engaged with an Engage Clinic therapist during a school-age support session">
        <span class="scrim"></span>
        <span class="item-badge" style="background:var(--green);">Ages 6 – 18</span>
        <div class="main-content">
          <h4 class="display">School-Age Support</h4>
          <p>Customised school-age intervention addressing behaviour, social skills, and academic challenges.</p>
          <a href="#programmes">Learn more <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M9 18l6-6-6-6"/></svg></a>
        </div>
        <span class="strip-label">School-Age Support</span>
      </div>

      <div class="hero-slide-item"
           data-heading="Finding their voice is just the beginning."
           data-paragraph="Specialist speech and language therapy helping children develop communication, social language, and expressive skills."
           data-accent="#F5A623" data-accent-soft="#FDECDD" data-accent-border="#F9CBA0">
        <img src="{{ asset('landingpage_assets/Speech_therapy.png') }}" alt="Child happily engaged with an Engage Clinic therapist during a speech therapy session">
        <span class="scrim"></span>
        <span class="item-badge" style="background:var(--orange);">Communication</span>
        <div class="main-content">
          <h4 class="display">Speech Therapy</h4>
          <p>Specialist speech and language therapy helping children develop communication, social language, and expressive skills.</p>
          <a href="#programmes">Learn more <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M9 18l6-6-6-6"/></svg></a>
        </div>
        <span class="strip-label">Speech Therapy</span>
      </div>
    </div>
  </div>
</section>

<!-- Stats -->
<section class="py-12 border-y border-[var(--border)] bg-white">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="reveal-group grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
      <div><div class="stat-number">200+</div><div class="stat-label">Families Supported</div></div>
      <div><div class="stat-number">10+</div><div class="stat-label">Years of Expertise</div></div>
      <div><div class="stat-number">5</div><div class="stat-label">Evidence-Based Programs</div></div>
      <div><div class="stat-number">100%</div><div class="stat-label">Family-Centered Care</div></div>
    </div>
  </div>
</section>

<!-- Programmes (bento) -->
<section id="programmes" class="py-16 md:py-24 dotted">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-12">
      <div>
        <span class="eyebrow-plain reveal-text">Our Programs</span>
        <h2 class="reveal-heading display mt-3 text-3xl md:text-4xl font-extrabold text-[var(--navy)] max-w-lg">Therapy tailored to every stage of growth</h2>
      </div>
      <div class="max-w-sm">
        <p class="reveal-text text-[var(--text-secondary)] text-sm leading-relaxed">Every program is built around your child's unique strengths, delivered in-home and in-community across Abu Dhabi.</p>
        <a href="{{ route('services') }}" class="inline-flex items-center gap-1.5 text-[var(--navy)] font-bold text-sm mt-3">View all services <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M9 18l6-6-6-6"/></svg></a>
      </div>
    </div>

    <div class="reveal-group grid grid-cols-1 md:grid-cols-4 gap-5" style="grid-auto-rows:170px;">
      <!-- ABA Intervention: large, 2x2 -->
      <div class="prog-card md:col-span-2 md:row-span-2" style="background:var(--indigo);">
        <span class="prog-watermark">ABA</span>
        <div class="prog-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.5 2a2.5 2.5 0 0 1 2.5 2.5v15a2.5 2.5 0 0 1-4.96.44A2.5 2.5 0 0 1 5 15.5v-1.44a2.5 2.5 0 0 1-1.5-4.5A2.5 2.5 0 0 1 5 5.5a2.5 2.5 0 0 1 4.5-3.5z"/><path d="M14.5 2a2.5 2.5 0 0 0-2.5 2.5v15a2.5 2.5 0 0 0 4.96.44A2.5 2.5 0 0 0 19 15.5v-1.44a2.5 2.5 0 0 0 1.5-4.5A2.5 2.5 0 0 0 19 5.5a2.5 2.5 0 0 0-4.5-3.5z"/></svg>
        </div>
        <span class="prog-label">Core Programme</span>
        <h3 class="prog-title">ABA Intervention Therapy</h3>
        <p class="text-sm mt-3 max-w-xs" style="color:rgba(255,255,255,.8);">Individualised programs built on each child's strengths using evidence-based behavioral science — delivered in your home.</p>
        <a href="{{ route('services') }}" class="btn-white !py-2.5 !px-5 !text-sm mt-5 w-fit">Learn more →</a>
      </div>

      <!-- Early Intervention -->
      <div class="prog-card md:col-span-1">
        <img src="{{ asset('landingpage_assets/Early_Intervention.png') }}" alt="Child happily engaged with an Engage Clinic therapist during an early intervention session">
        <span class="scrim"></span>
        <span class="tag-pill tag-lime" style="position:static;display:inline-block;margin-bottom:auto;">✦ Milestone Focus</span>
        <div>
          <span class="prog-label">Ages 2 – 6</span>
          <h3 class="prog-title !text-lg">Early Intervention</h3>
        </div>
      </div>

      <!-- Speech Therapy -->
      <div class="prog-card md:col-span-1" style="background:var(--orange);">
        <span class="prog-watermark">S</span>
        <div class="prog-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <span class="prog-label">Communication</span>
        <h3 class="prog-title !text-lg">Speech Therapy</h3>
      </div>

      <!-- School-Age Support -->
      <div class="prog-card md:col-span-2">
        <img src="{{ asset('landingpage_assets/School_Age_Support.png') }}" alt="Children happily engaged with an Engage Clinic therapist during a school-age support session">
        <span class="scrim"></span>
        <span class="tag-pill tag-green" style="position:static;display:inline-block;margin-bottom:auto;">Ages 6 – 18</span>
        <h3 class="prog-title">School-Age Support</h3>
      </div>

      <!-- Parent Training -->
      <div class="prog-card md:col-span-2">
        <img src="{{ asset('landingpage_assets/Parent_training.png') }}" alt="Child happily building blocks with a parent during a parent training session">
        <span class="scrim"></span>
        <div class="prog-icon" style="background:rgba(255,255,255,.22);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <span class="prog-label">Family Support</span>
        <h3 class="prog-title">Parent Training</h3>
      </div>

      <!-- Behavioural Assessment -->
      <div class="prog-card md:col-span-2" style="background:var(--navy-deep);">
        <span class="prog-watermark">B</span>
        <div class="prog-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <span class="prog-label">Assessment</span>
        <h3 class="prog-title">Behavioural Assessment</h3>
      </div>
    </div>
  </div>
</section>

<!-- GIFTED values -->
<section class="py-16 md:py-24 bg-white border-y border-[var(--border)]">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center mb-14">
      <span class="eyebrow-plain reveal-text">Our Values</span>
      <h2 class="reveal-heading display mt-3 text-3xl md:text-4xl font-extrabold text-[var(--navy)]">Every child is <span style="color:var(--pink);">GIFTED</span></h2>
      <p class="reveal-text mt-4 text-[var(--text-secondary)] text-lg">Our values are the operating principles behind every session, every plan, and every family relationship we build.</p>
    </div>
    <div class="reveal-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div class="gifted-tile" style="background:var(--lime);color:var(--navy-deep);">
        <span class="gifted-letter">G</span>
        <span class="gifted-title">Growth</span>
        <span class="gifted-desc">Shared progress for children, families, and staff</span>
      </div>
      <div class="gifted-tile" style="background:var(--indigo);color:#fff;">
        <span class="gifted-letter">I</span>
        <span class="gifted-title">Inclusion</span>
        <span class="gifted-desc">Full participation in home, school, and community</span>
      </div>
      <div class="gifted-tile" style="background:var(--pink-mid);color:var(--navy-deep);">
        <span class="gifted-letter">F</span>
        <span class="gifted-title">Family-Centric</span>
        <span class="gifted-desc">Empathy-led, individualized family support</span>
      </div>
      <div class="gifted-tile" style="background:var(--orange);color:var(--navy-deep);">
        <span class="gifted-letter">T</span>
        <span class="gifted-title">Trust</span>
        <span class="gifted-desc">Transparent, collaborative partnerships</span>
      </div>
      <div class="gifted-tile" style="background:var(--gold);color:var(--navy-deep);">
        <span class="gifted-letter">E</span>
        <span class="gifted-title">Excellence</span>
        <span class="gifted-desc">Science-based, high-standard care</span>
      </div>
      <div class="gifted-tile" style="background:var(--maroon);color:#fff;">
        <span class="gifted-letter">D</span>
        <span class="gifted-title">Dignity</span>
        <span class="gifted-desc">Child-led, culturally sensitive therapy</span>
      </div>
    </div>
  </div>
</section>

<!-- Approach: We ENRICH -->
<section id="about" class="py-16 md:py-24 dotted">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-14 items-center">
    <div class="relative">
      <div class="card photo-wrap rounded-[26px] overflow-hidden aspect-[4/5] relative">
        <img src="{{ asset('about_us/founder.png') }}" alt="Hong Chun Tan, Founder & Clinical Director of Engage Clinic" class="w-full h-full object-cover object-top">
      </div>
      <div class="absolute -bottom-16 left-6 right-6 sm:right-auto sm:w-80 p-4 rounded-[18px] flex items-center gap-3" style="background:#F2F1EC;box-shadow:0 14px 32px -14px rgba(14,46,76,.25);">
        <div class="icon-box" style="background:var(--pink);color:#fff;border-color:var(--pink);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div>
          <p class="text-sm font-bold text-[var(--navy)] leading-none">Free 30-Min Consultation</p>
          <p class="text-xs text-[var(--text-secondary)] mt-1.5">with Hong Chun Tan, BCBA · Health insurance accepted</p>
        </div>
      </div>
    </div>

    <div class="mt-6 lg:mt-0">
      <span class="eyebrow-plain reveal-text">Our Approach</span>
      <h2 class="reveal-heading display mt-3 text-3xl md:text-4xl font-extrabold text-[var(--navy)]">We don't just treat. We <span style="color:var(--pink);">ENRICH</span>.</h2>
      <p class="reveal-text mt-4 text-[var(--text-secondary)] leading-relaxed">Our ENRICH framework guides every treatment plan — ensuring progress that extends far beyond the therapy room into real life.</p>

      <div class="reveal-group grid grid-cols-1 sm:grid-cols-2 gap-3 mt-8">
        <div class="enrich-item"><span class="enrich-dot" style="background:var(--lime);"></span><h4>Enrich Growth</h4><p>Functional life skills and independence</p></div>
        <div class="enrich-item"><span class="enrich-dot" style="background:var(--indigo);"></span><h4>Enrich Skills</h4><p>Real-world settings — parks, cafés, community</p></div>
        <div class="enrich-item"><span class="enrich-dot" style="background:var(--pink);"></span><h4>Enrich Families</h4><p>Caregiver partnership and transparency</p></div>
        <div class="enrich-item"><span class="enrich-dot" style="background:var(--orange);"></span><h4>Enrich Journeys</h4><p>Personalized, adaptive progress plans</p></div>
      </div>
    </div>
  </div>
</section>

<!-- Accreditation -->
<section class="py-16 md:py-20 bg-white border-y border-[var(--border)]">
  <div class="max-w-3xl mx-auto px-6 lg:px-8 text-center">
    <h2 class="reveal-heading display text-2xl md:text-3xl font-extrabold" style="color:var(--pink);">Officially Accredited. Globally Aligned.</h2>
    <p class="reveal-text mt-4 text-[var(--text-secondary)] leading-relaxed">Our certifications reflect our unwavering commitment to global standards in special education — ensuring every child receives expert support, compassionate care, and a future built on excellence.</p>

    <div class="reveal-group grid grid-cols-3 items-center gap-2 sm:gap-8 md:gap-12 mt-12">
      <div class="flex items-center justify-center h-16 sm:h-28 md:h-36">
        <img src="{{ asset('accrediation/accrediation1.png') }}" alt="QABA Behavioral Health Credentialing — Approved Coursework Provider" class="max-w-full max-h-full object-contain">
      </div>
      <div class="flex items-center justify-center h-20 sm:h-36 md:h-48">
        <img src="{{ asset('accrediation/accrediation3.png') }}" alt="QABA Approved Training Program" class="max-w-full max-h-full object-contain">
      </div>
      <div class="flex items-center justify-center h-16 sm:h-28 md:h-36">
        <img src="{{ asset('accrediation/accrediation2.png') }}" alt="ACE — BACB Authorized Continuing Education Provider" class="max-w-full max-h-full object-contain">
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section id="testimonials" class="py-16 md:py-24 dotted">
  <div class="max-w-2xl mx-auto px-6 lg:px-8 text-center">
    <span class="eyebrow-plain reveal-text">Family Stories</span>
    <h2 class="reveal-heading display mt-3 text-3xl md:text-4xl font-extrabold text-[var(--navy)] mb-10">Words from families we serve</h2>

    <div id="testimonialTrack">
      <div class="testimonial-slide">
        <div class="flex justify-center gap-1 text-[var(--orange)] mb-5">★★★★★</div>
        <p class="text-lg text-[var(--navy)] italic leading-relaxed">"The team at Engage is truly an extended family. Our son went from severe daily tantrums to a child who laughs and plays with his friends. We have hope again."</p>
        <p class="font-bold text-[var(--navy)] mt-6">Sarah M.</p>
        <p class="text-sm text-[var(--text-muted)]">Parent of a 6-year-old</p>
      </div>
      <div class="testimonial-slide hidden">
        <div class="flex justify-center gap-1 text-[var(--orange)] mb-5">★★★★★</div>
        <p class="text-lg text-[var(--navy)] italic leading-relaxed">"Home-based sessions changed everything for us — no commute, no meltdowns in the car, just consistent, patient therapists our daughter trusts."</p>
        <p class="font-bold text-[var(--navy)] mt-6">Omar K.</p>
        <p class="text-sm text-[var(--text-muted)]">Parent of a 4-year-old</p>
      </div>
      <div class="testimonial-slide hidden">
        <div class="flex justify-center gap-1 text-[var(--orange)] mb-5">★★★★★</div>
        <p class="text-lg text-[var(--navy)] italic leading-relaxed">"The BCBA team includes us in every decision. It doesn't feel like therapy happening to our family — it feels like a partnership."</p>
        <p class="font-bold text-[var(--navy)] mt-6">Layla H.</p>
        <p class="text-sm text-[var(--text-muted)]">Parent of an 8-year-old</p>
      </div>
    </div>

    <div class="flex items-center justify-center gap-1.5 mt-8" id="testimonialDots">
      <button class="carousel-dot active" onclick="goToTestimonial(0)" aria-label="Story 1"></button>
      <button class="carousel-dot" onclick="goToTestimonial(1)" aria-label="Story 2"></button>
      <button class="carousel-dot" onclick="goToTestimonial(2)" aria-label="Story 3"></button>
    </div>
  </div>
</section>

<!-- CTA banner -->
<section class="py-16 md:py-20" style="background:var(--pink);">
  <div class="max-w-3xl mx-auto px-6 lg:px-8 text-center">
    <h2 class="reveal-heading display text-3xl md:text-4xl font-extrabold text-white">Ready to start your child's journey?</h2>
    <p class="reveal-text text-white/85 mt-4 max-w-md mx-auto">Book a free 30-minute consultation. We accept health insurance and serve all of Abu Dhabi.</p>
    <div class="flex flex-wrap items-center justify-center gap-3 mt-8">
      <a href="#" onclick="event.preventDefault(); openBookingModal();" class="btn-white">Book Free Consultation →</a>
      <a href="tel:+971508846801" class="btn-outline-white">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        +971 50 884 6801
      </a>
    </div>
  </div>
</section>

<!-- Booking modal -->
<div class="modal-overlay" id="bookingOverlay">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h3 class="display">Book a Free Consultation</h3>
        <p>30 minutes · No obligation · Health insurance accepted</p>
      </div>
      <button type="button" class="modal-close" onclick="closeBookingModal()" aria-label="Close">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="modal-stepper">
      <div class="step-node" id="stepNode1"><span class="step-circle">1</span><span class="step-label">Pick a Date</span></div>
      <span class="step-line"></span>
      <div class="step-node" id="stepNode2"><span class="step-circle">2</span><span class="step-label">Choose a Time</span></div>
      <span class="step-line"></span>
      <div class="step-node" id="stepNode3"><span class="step-circle">3</span><span class="step-label">Your Details</span></div>
    </div>

    <div class="modal-body">

      <!-- Step 1: Date -->
      <div class="modal-step" id="modalStep1">
        <div class="cal-nav">
          <button type="button" class="carousel-btn" onclick="changeMonth(-1)" aria-label="Previous month">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <span id="calMonthLabel" class="display"></span>
          <button type="button" class="carousel-btn" onclick="changeMonth(1)" aria-label="Next month">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>
        <div class="cal-weekdays">
          <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
        </div>
        <div class="cal-grid" id="calGrid"></div>
        <div class="cal-legend">
          <span><i class="swatch today"></i> Today</span>
          <span><i class="swatch unavailable"></i> Unavailable (Fri–Sat)</span>
        </div>
        <div class="modal-footer">
          <span></span>
          <button type="button" class="btn-pink" id="dateContinueBtn" disabled onclick="goToStep(2)">Continue →</button>
        </div>
      </div>

      <!-- Step 2: Time -->
      <div class="modal-step hidden" id="modalStep2">
        <button type="button" class="step-back" onclick="goToStep(1)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <h4 class="display modal-step-title">Choose a time</h4>
        <p class="modal-step-subtitle" id="timeStepDate"></p>
        <div class="time-grid" id="timeGrid"></div>
        <p class="tz-note">All times are Abu Dhabi Standard Time (GST, UTC+4)</p>
        <div class="modal-footer">
          <button type="button" class="btn-ghost" onclick="goToStep(1)">Back</button>
          <button type="button" class="btn-pink" id="timeContinueBtn" disabled onclick="goToStep(3)">Continue →</button>
        </div>
      </div>

      <!-- Step 3: Details -->
      <div class="modal-step hidden" id="modalStep3">
        <button type="button" class="step-back" onclick="goToStep(2)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <h4 class="display modal-step-title">Your details</h4>
        <p class="modal-step-subtitle" id="detailsStepDate"></p>

        <div class="booking-summary">
          <div class="icon-box !rounded-full">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div>
            <p class="font-bold text-[var(--navy)] text-sm" id="summaryDate"></p>
            <p class="text-xs text-[var(--text-secondary)] mt-0.5" id="summaryTime"></p>
          </div>
        </div>

        <div id="bookingSuccess" style="display:none;background:#E4F6EB;color:#1E8A4C;padding:12px 14px;border-radius:10px;margin:15px 0 0;font-size:13.5px;font-weight:600;border:1px solid #BFE9CE;"></div>
        <div id="bookingError" style="display:none;background:#FEF2F2;color:#B91C1C;padding:12px 14px;border-radius:10px;margin:15px 0 0;font-size:13.5px;font-weight:600;border:1px solid #FECACA;"></div>

        <form id="bookingForm" class="space-y-4 mt-5" onsubmit="submitBooking(event)">
          <div class="grid grid-cols-1 sm:grid-cols-[1fr_110px] gap-3">
            <div>
              <label class="crm-label">Child's name *</label>
              <input type="text" id="bk_child_name" placeholder="e.g. Hamad" class="crm-input" required>
            </div>
            <div>
              <label class="crm-label">Age</label>
              <input type="text" id="bk_child_age" placeholder="5" class="crm-input">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="crm-label">Full name *</label>
              <input type="text" id="bk_name" placeholder="Parent / Guardian name" class="crm-input" required>
            </div>
            <div>
              <label class="crm-label">Phone *</label>
              <input type="tel" id="bk_phone" placeholder="+971 5x xxx xxxx" class="crm-input" required>
            </div>
          </div>
          <div>
            <label class="crm-label">Email address *</label>
            <input type="email" id="bk_email" placeholder="you@email.com" class="crm-input" required>
          </div>
          <div>
            <label class="crm-label">Service of interest</label>
            <select id="bk_service" class="crm-select">
              <option value="">Select a service…</option>
              <option value="ABA therapy">ABA therapy</option>
              <option value="Speech therapy">Speech therapy</option>
              <option value="Early intervention">Early intervention</option>
              <option value="School-age support">School-age support</option>
              <option value="Parent training">Parent training</option>
              <option value="Behavioural assessment">Behavioural assessment</option>
            </select>
          </div>
          <div>
            <label class="crm-label">Notes (optional)</label>
            <textarea id="bk_notes" rows="3" placeholder="Brief note about your child's needs or any questions you have…" class="crm-input" style="resize:vertical;"></textarea>
          </div>
          <div class="modal-footer !border-t-0 !pt-0">
            <button type="button" class="btn-ghost" onclick="goToStep(2)">Back</button>
            <button type="submit" class="btn-pink" id="confirmBookingBtn">
              Confirm Booking
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>
          <p class="text-center text-xs text-[var(--text-muted)]">We'll confirm your slot within 24 hours. Health insurance accepted.</p>
        </form>
      </div>

    </div>
  </div>
</div>

<!-- Floating soft-opening countdown widget -->
<div class="float-widget">
  <button class="float-toggle" id="floatToggle" onclick="toggleFloatPanel()">
    ✦ SOFT OPENING&nbsp;Aug 22, 2026
    <svg id="floatChevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="transition:transform .2s;"><path d="M6 9l6 6 6-6"/></svg>
  </button>
  <div class="float-panel" id="floatPanel">
    <p>Counting down to our grand soft opening in Abu Dhabi!</p>
    <div class="countdown-grid" id="countdownGrid">
      <div class="countdown-box"><div class="num" id="cdDays">00</div><div class="unit">Days</div></div>
      <div class="countdown-box"><div class="num" id="cdHrs">00</div><div class="unit">Hrs</div></div>
      <div class="countdown-box"><div class="num" id="cdMin">00</div><div class="unit">Min</div></div>
      <div class="countdown-box"><div class="num" id="cdSec">00</div><div class="unit">Sec</div></div>
    </div>
    <p class="float-address">Office 1203, ADCP Commercial Tower-C<br>Electra Street, Abu Dhabi, UAE</p>
  </div>
</div>

@endsection

@push('scripts')
<script>
  // Hero carousel — synced headline/copy/accent + animated card widths
  const heroItems = Array.from(document.querySelectorAll('.hero-slide-item'));
  const heroDots = document.querySelectorAll('#galleryDots .carousel-dot');
  const heroEyebrow = document.getElementById('heroEyebrow');
  const heroHeading = document.getElementById('heroHeading');
  const heroParagraph = document.getElementById('heroParagraph');
  const heroBookBtn = document.getElementById('heroBookBtn');
  let heroIndex = 0;
  let heroAutoplay;
  let heroCopyTimer;

  function goToHeroSlide(i) {
    heroIndex = (i + heroItems.length) % heroItems.length;
    const active = heroItems[heroIndex];

    heroItems.forEach((el, idx) => el.classList.toggle('is-active', idx === heroIndex));
    heroDots.forEach((d, idx) => {
      d.classList.toggle('active', idx === heroIndex);
      d.style.background = idx === heroIndex ? active.dataset.accent : '';
    });

    const heroCopy = [heroEyebrow, heroHeading, heroParagraph, heroBookBtn];
    heroCopy.forEach(el => el.classList.add('hero-copy-fade'));

    clearTimeout(heroCopyTimer);
    heroCopyTimer = setTimeout(() => {
      heroHeading.textContent = active.dataset.heading;
      heroParagraph.textContent = active.dataset.paragraph;
      heroEyebrow.style.background = active.dataset.accentSoft;
      heroEyebrow.style.borderColor = active.dataset.accentBorder;
      heroEyebrow.style.color = active.dataset.accent;
      heroBookBtn.style.background = active.dataset.accent;
      // force a reflow so the re-entry transition retriggers cleanly
      void heroHeading.offsetWidth;
      heroCopy.forEach(el => el.classList.remove('hero-copy-fade'));
    }, 200);

    restartHeroAutoplay();
  }

  function restartHeroAutoplay() {
    clearInterval(heroAutoplay);
    heroAutoplay = setInterval(() => goToHeroSlide(heroIndex + 1), 5500);
  }

  heroItems.forEach((el, idx) => el.addEventListener('click', () => { if (idx !== heroIndex) goToHeroSlide(idx); }));
  restartHeroAutoplay();

  // Testimonial carousel
  const slides = document.querySelectorAll('.testimonial-slide');
  const testimonialDots = document.querySelectorAll('#testimonialDots .carousel-dot');
  let currentSlide = 0;
  function goToTestimonial(i) {
    slides.forEach((s, idx) => s.classList.toggle('hidden', idx !== i));
    testimonialDots.forEach((d, idx) => d.classList.toggle('active', idx === i));
    currentSlide = i;
  }
  setInterval(() => { goToTestimonial((currentSlide + 1) % slides.length); }, 6000);

  // Floating soft-opening panel + countdown
  const floatPanel = document.getElementById('floatPanel');
  const floatChevron = document.getElementById('floatChevron');
  function toggleFloatPanel() {
    floatPanel.classList.toggle('open');
    floatChevron.style.transform = floatPanel.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
  }
  const openingDate = new Date('2026-08-22T00:00:00+04:00').getTime();
  function updateCountdown() {
    const diff = openingDate - Date.now();
    if (diff <= 0) { return; }
    const days = Math.floor(diff / 86400000);
    const hrs = Math.floor((diff % 86400000) / 3600000);
    const min = Math.floor((diff % 3600000) / 60000);
    const sec = Math.floor((diff % 60000) / 1000);
    document.getElementById('cdDays').textContent = String(days).padStart(2, '0');
    document.getElementById('cdHrs').textContent = String(hrs).padStart(2, '0');
    document.getElementById('cdMin').textContent = String(min).padStart(2, '0');
    document.getElementById('cdSec').textContent = String(sec).padStart(2, '0');
  }
  updateCountdown();
  setInterval(updateCountdown, 1000);

  // ---- Booking modal ----
  const bookingState = { date: null, time: null, monthOffset: 0 };
  const TIME_SLOTS = ['9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','1:00 PM','1:30 PM','2:00 PM','2:30 PM','3:00 PM','3:30 PM','4:00 PM','4:30 PM'];
  const WEEKDAY_FMT = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };

  function openBookingModal() {
    if (typeof mobileMenu !== 'undefined' && mobileMenu) mobileMenu.classList.add('hidden');
    document.getElementById('bookingOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    bookingState.date = null;
    bookingState.time = null;
    bookingState.monthOffset = 0;
    document.getElementById('dateContinueBtn').disabled = true;
    document.getElementById('timeContinueBtn').disabled = true;
    document.getElementById('bookingForm').reset();
    document.getElementById('bookingSuccess').style.display = 'none';
    document.getElementById('bookingError').style.display = 'none';
    document.getElementById('bookingForm').style.display = '';
    renderCalendar();
    goToStep(1);
  }

  function closeBookingModal() {
    document.getElementById('bookingOverlay').classList.remove('open');
    document.body.style.overflow = '';
  }
  document.getElementById('bookingOverlay').addEventListener('click', (e) => {
    if (e.target.id === 'bookingOverlay') closeBookingModal();
  });

  function goToStep(step) {
    [1, 2, 3].forEach(n => {
      document.getElementById('modalStep' + n).classList.toggle('hidden', n !== step);
      const node = document.getElementById('stepNode' + n);
      node.classList.toggle('active', n === step);
      node.classList.toggle('done', n < step);
      node.querySelector('.step-circle').innerHTML = n < step
        ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>'
        : n;
    });
    if (step === 2 && bookingState.date) {
      document.getElementById('timeStepDate').textContent = bookingState.date.toLocaleDateString('en-GB', WEEKDAY_FMT);
      renderTimeSlots();
    }
    if (step === 3 && bookingState.date) {
      const dateStr = bookingState.date.toLocaleDateString('en-GB', WEEKDAY_FMT);
      document.getElementById('detailsStepDate').textContent = dateStr + ' · ' + bookingState.time;
      document.getElementById('summaryDate').textContent = dateStr;
      document.getElementById('summaryTime').textContent = bookingState.time + ' · 30 min free consultation';
    }
  }

  function changeMonth(dir) {
    bookingState.monthOffset += dir;
    renderCalendar();
  }

  function renderCalendar() {
    const base = new Date();
    base.setDate(1);
    base.setMonth(base.getMonth() + bookingState.monthOffset);
    document.getElementById('calMonthLabel').textContent = base.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });

    const today = new Date(); today.setHours(0, 0, 0, 0);
    const year = base.getFullYear(), month = base.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    for (let i = 0; i < firstDay; i++) {
      const cell = document.createElement('div');
      cell.className = 'cal-day faded';
      grid.appendChild(cell);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const cellDate = new Date(year, month, d);
      const cell = document.createElement('button');
      cell.type = 'button';
      cell.className = 'cal-day';
      cell.textContent = d;
      const isPast = cellDate < today;
      const isWeekend = cellDate.getDay() === 5 || cellDate.getDay() === 6;
      const isToday = cellDate.getTime() === today.getTime();
      const isSelected = bookingState.date && cellDate.getTime() === bookingState.date.getTime();
      if (isPast || isWeekend) cell.classList.add('disabled');
      if (isToday) cell.classList.add('today');
      if (isSelected) cell.classList.add('selected');
      if (!isPast && !isWeekend) {
        cell.addEventListener('click', () => {
          bookingState.date = cellDate;
          renderCalendar();
          document.getElementById('dateContinueBtn').disabled = false;
        });
      }
      grid.appendChild(cell);
    }
  }

  function renderTimeSlots() {
    const grid = document.getElementById('timeGrid');
    grid.innerHTML = '';
    TIME_SLOTS.forEach(t => {
      const el = document.createElement('button');
      el.type = 'button';
      el.className = 'time-slot' + (bookingState.time === t ? ' selected' : '');
      el.textContent = t;
      el.addEventListener('click', () => {
        bookingState.time = t;
        document.getElementById('timeContinueBtn').disabled = false;
        renderTimeSlots();
      });
      grid.appendChild(el);
    });
  }

  function submitBooking(event) {
    event.preventDefault();
    const childName = document.getElementById('bk_child_name').value.trim();
    const childAge = document.getElementById('bk_child_age').value.trim();
    const name = document.getElementById('bk_name').value.trim();
    const phone = document.getElementById('bk_phone').value.trim();
    const email = document.getElementById('bk_email').value.trim();
    const service = document.getElementById('bk_service').value;
    const notes = document.getElementById('bk_notes').value.trim();
    const successBox = document.getElementById('bookingSuccess');
    const errorBox = document.getElementById('bookingError');
    successBox.style.display = 'none';
    errorBox.style.display = 'none';

    const dateStr = bookingState.date ? bookingState.date.toLocaleDateString('en-GB', WEEKDAY_FMT) : '';
    const combinedNotes = `Requested: ${dateStr} at ${bookingState.time} (30-min free consultation)\nEmail: ${email}` + (notes ? `\n${notes}` : '');

    const btn = document.getElementById('confirmBookingBtn');
    const originalHTML = btn.innerHTML;
    btn.textContent = 'Booking...';
    btn.disabled = true;

    fetch('/api/leads', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        child_name: childName,
        child_age: childAge,
        parent_guardian_name: name,
        phone: phone,
        source: 'Website',
        interested_in: service,
        notes: combinedNotes,
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('bookingForm').style.display = 'none';
        successBox.style.display = 'block';
        successBox.textContent = `✅ Booking request received for ${dateStr} at ${bookingState.time}. We'll confirm within 24 hours.`;
      } else {
        errorBox.style.display = 'block';
        const errors = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Something went wrong. Please try again.');
        errorBox.textContent = '❌ ' + errors;
      }
    })
    .catch(() => {
      errorBox.style.display = 'block';
      errorBox.textContent = '❌ Network error. Please check your connection and try again.';
    })
    .finally(() => {
      btn.innerHTML = originalHTML;
      btn.disabled = false;
    });
  }
</script>
@endpush

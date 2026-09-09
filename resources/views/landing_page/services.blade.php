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
              @foreach ($publicServices as $service)
                <option value="{{ $service->name }}">{{ $service->name }}</option>
              @endforeach
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

@endsection

@push('scripts')
<script>
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

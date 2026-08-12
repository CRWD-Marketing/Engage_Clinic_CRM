@extends('layouts.landing')

@section('title', 'Engage Clinic · Blog')

@php
  $activePage = 'blog';
@endphp

@section('content')

<!-- Hero -->
<section class="py-20 md:py-28 text-center" style="background:var(--navy-deep);">
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
        <span class="service-badge w-fit" style="background:{{ $fa['soft'] }};color:{{ $fa['color'] }};">{{ $featured['tag'] }}</span>
        <h2 class="display mt-4 text-2xl sm:text-3xl font-extrabold text-[var(--navy)] leading-tight">{{ $featured['title'] }}</h2>
        <p class="mt-3 text-[var(--text-secondary)] leading-relaxed">{{ $featured['excerpt'] }}</p>
        <div class="flex items-center gap-2 mt-6 text-xs text-[var(--text-muted)]">
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
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

@push('scripts')
<script>
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

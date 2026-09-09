@extends('layouts.landing')

@section('title', 'Engage Clinic · Careers')

@php
  $activePage = 'careers';
@endphp

@section('content')

<!-- Hero -->
<section class="js-hero py-20 md:py-28 text-center" style="background:var(--navy-deep);">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <span class="eyebrow-plain" style="color:var(--pink-mid);">Careers</span>
    <h1 class="display mt-4 text-3xl sm:text-5xl font-extrabold leading-[1.15] text-white">Grow with us. Change lives with us.</h1>
    <p class="mt-5 text-white/75 text-base sm:text-lg leading-relaxed">We're building a team of passionate clinicians committed to raising the standard of behavioral therapy in the UAE. If you believe every child is GIFTED, you belong here.</p>
  </div>
</section>

@php
  $perks = [
    ['icon' => 'award', 'accent' => 'indigo', 'title' => 'Clinical Excellence', 'desc' => 'Work alongside BCBA-credentialed leaders. Continuous professional development built in.'],
    ['icon' => 'heart', 'accent' => 'pink', 'title' => 'Family-First Culture', 'desc' => 'We treat staff the same way we treat families — with transparency, respect, and investment in your growth.'],
    ['icon' => 'pin', 'accent' => 'green', 'title' => 'Abu Dhabi + Beyond', 'desc' => "Visa sponsorship available. Join an international team in one of the UAE's most vibrant cities."],
    ['icon' => 'book', 'accent' => 'orange', 'title' => 'Ongoing Supervision', 'desc' => 'Regular BCBA supervision hours, case consultations, and peer learning to sharpen your clinical skills.'],
    ['icon' => 'users', 'accent' => 'indigo', 'title' => 'Collaborative Team', 'desc' => 'Small, tight-knit team where your voice matters. Share ideas and celebrate outcomes together.'],
    ['icon' => 'sparkle', 'accent' => 'pink', 'title' => 'Meaningful Impact', 'desc' => "Every day you change a child's developmental trajectory — and a family's life."],
  ];
  $perkAccent = [
    'indigo' => ['soft' => '#EEEEFC', 'color' => 'var(--indigo)'],
    'pink' => ['soft' => 'var(--pink-soft)', 'color' => 'var(--pink)'],
    'green' => ['soft' => '#E6F7EC', 'color' => 'var(--green)'],
    'orange' => ['soft' => '#FDECDD', 'color' => 'var(--orange)'],
  ];
  $perkIcons = [
    'award' => '<circle cx="12" cy="8" r="6"/><path d="M15.5 13.5 17 22l-5-3-5 3 1.5-8.5"/>',
    'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/>',
    'pin' => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    'book' => '<path d="M2 4h6a4 4 0 0 1 4 4v12a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v12a3 3 0 0 1 3-3h7z"/>',
    'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'sparkle' => '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>',
  ];

@endphp

<!-- Why join -->
<section class="py-16 md:py-24 dotted">
  <div class="max-w-6xl mx-auto px-6 lg:px-8">
    <h2 class="reveal-heading display text-3xl md:text-4xl font-extrabold text-[var(--navy)] text-center mb-14">Why join Engage?</h2>
    <div class="reveal-group grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach ($perks as $perk)
        @php $pa = $perkAccent[$perk['accent']]; @endphp
        <div class="card p-7">
          <div class="icon-box" style="background:{{ $pa['soft'] }};color:{{ $pa['color'] }};border-color:transparent;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $perkIcons[$perk['icon']] !!}</svg>
          </div>
          <h4 class="font-bold text-[var(--navy)] mt-5 mb-1.5">{{ $perk['title'] }}</h4>
          <p class="text-[var(--text-secondary)] text-sm leading-relaxed">{{ $perk['desc'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Current opportunities -->
<section class="py-16 md:py-24 bg-white border-y border-[var(--border)]">
  <div class="max-w-4xl mx-auto px-6 lg:px-8">
    <span class="eyebrow-plain reveal-text">Open Positions</span>
    <h2 class="reveal-heading display mt-3 text-3xl md:text-4xl font-extrabold text-[var(--navy)] mb-10">Current opportunities</h2>

    <div class="reveal-group space-y-4">
      @forelse ($jobPostings as $job)
        <details class="job-item">
          <summary class="flex items-center justify-between gap-4">
            <div>
              <h4 class="font-bold text-[var(--navy)]">{{ $job->title }}</h4>
              @php
                $jobTypeLocation = collect([$job->employment_type, $job->location])->filter()->implode(' · ');
              @endphp
              @if ($jobTypeLocation)
                <p class="job-type mt-1">{{ $jobTypeLocation }}</p>
              @endif
            </div>
            <svg class="job-chevron shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--navy)" stroke-width="2.5"><path d="M18 15l-6-6-6 6"/></svg>
          </summary>
          <div class="job-body">
            @if ($job->description)
              <p class="text-[var(--text-secondary)] text-sm leading-relaxed">{{ $job->description }}</p>
            @endif
            @if (!empty($job->requirements))
              <p class="job-req-label">Requirements</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mb-6">
                @foreach ($job->requirements as $req)
                  <div class="service-check">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--pink)" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    {{ $req }}
                  </div>
                @endforeach
              </div>
            @endif
            <button type="button" class="btn-pink !text-sm" onclick="openApplyModal({{ $job->id }}, @js($job->title))">Apply Now →</button>
          </div>
        </details>
      @empty
        <div class="text-center py-10 text-[var(--text-secondary)] text-sm">
          No open positions right now — check back soon, or reach out at
          <a href="mailto:careers@engagebehavior.com" class="font-bold" style="color: var(--pink);">careers@engagebehavior.com</a>.
        </div>
      @endforelse
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

<!-- Apply modal -->
<div class="modal-overlay" id="applyOverlay">
  <div class="modal-card">
    <div class="modal-header">
      <div>
        <h3 class="display">Apply Now</h3>
        <p id="applyJobTitle">&nbsp;</p>
      </div>
      <button type="button" class="modal-close" onclick="closeApplyModal()" aria-label="Close">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="modal-body">
      <div id="applySuccess" style="display:none;background:#E4F6EB;color:#1E8A4C;padding:12px 14px;border-radius:10px;margin:0 0 15px;font-size:13.5px;font-weight:600;border:1px solid #BFE9CE;"></div>
      <div id="applyError" style="display:none;background:#FEF2F2;color:#B91C1C;padding:12px 14px;border-radius:10px;margin:0 0 15px;font-size:13.5px;font-weight:600;border:1px solid #FECACA;"></div>

      <form id="applyForm" class="space-y-4" onsubmit="submitApplication(event)">
        <input type="hidden" id="ap_job_id" name="job_posting_id">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="crm-label">First name *</label>
            <input type="text" id="ap_first_name" name="first_name" placeholder="Jane" class="crm-input" required>
          </div>
          <div>
            <label class="crm-label">Last name *</label>
            <input type="text" id="ap_last_name" name="last_name" placeholder="Doe" class="crm-input" required>
          </div>
        </div>
        <div>
          <label class="crm-label">Email address *</label>
          <input type="email" id="ap_email" name="email" placeholder="you@gmail.com" class="crm-input" required>
        </div>
        <div>
          <label class="crm-label">Years of experience</label>
          <input type="text" id="ap_years_experience" name="years_experience" placeholder="e.g. 3" class="crm-input">
        </div>
        <div>
          <label class="crm-label">Resume *</label>
          <input type="file" id="ap_resume" name="resume" accept=".pdf,.doc,.docx" class="crm-input" required>
          <p class="text-xs mt-1.5" style="color: var(--text-muted);">PDF, DOC, or DOCX — max 5MB.</p>
        </div>
        <div>
          <label class="crm-label">Cover letter (optional)</label>
          <textarea id="ap_cover_letter" name="cover_letter" rows="3" placeholder="Tell us why you'd be a great fit…" class="crm-input" style="resize:vertical;"></textarea>
        </div>
        <div class="modal-footer !border-t-0 !pt-0">
          <span></span>
          <button type="submit" class="btn-pink" id="submitApplyBtn">
            Submit Application
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('head')
<style>
  .job-item{background:var(--bg);border-radius:20px;padding:22px 24px;}
  .job-item summary{cursor:pointer;list-style:none;}
  .job-item summary::-webkit-details-marker{display:none;}
  .job-type{font-family:ui-monospace,'SF Mono',Menlo,monospace;font-size:12px;color:var(--pink);}
  .job-chevron{transition:transform .2s ease;margin-top:4px;}
  .job-item[open] .job-chevron{transform:rotate(180deg);}
  .job-body{border-top:1px solid var(--border);margin-top:18px;padding-top:18px;}
  .job-req-label{font-size:11px;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;margin:14px 0 10px;}
</style>
@endpush

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

  // ---- Apply modal ----
  const applyOverlay = document.getElementById('applyOverlay');

  function openApplyModal(jobId, jobTitle) {
    if (typeof mobileMenu !== 'undefined' && mobileMenu) mobileMenu.classList.add('hidden');
    document.getElementById('ap_job_id').value = jobId;
    document.getElementById('applyJobTitle').textContent = jobTitle;
    document.getElementById('applyForm').reset();
    document.getElementById('ap_job_id').value = jobId;
    document.getElementById('applySuccess').style.display = 'none';
    document.getElementById('applyError').style.display = 'none';
    document.getElementById('applyForm').style.display = '';
    applyOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeApplyModal() {
    applyOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }
  applyOverlay.addEventListener('click', (e) => {
    if (e.target.id === 'applyOverlay') closeApplyModal();
  });

  function submitApplication(event) {
    event.preventDefault();
    const successBox = document.getElementById('applySuccess');
    const errorBox = document.getElementById('applyError');
    successBox.style.display = 'none';
    errorBox.style.display = 'none';

    const resumeInput = document.getElementById('ap_resume');
    const resumeFile = resumeInput.files[0];
    if (resumeFile) {
      const allowedExt = ['pdf', 'doc', 'docx'];
      const ext = resumeFile.name.split('.').pop().toLowerCase();
      if (!allowedExt.includes(ext)) {
        errorBox.style.display = 'block';
        errorBox.textContent = '❌ Please upload a PDF, DOC, or DOCX file.';
        return;
      }
      if (resumeFile.size > 5 * 1024 * 1024) {
        errorBox.style.display = 'block';
        errorBox.textContent = '❌ Resume must be smaller than 5MB.';
        return;
      }
    }

    const form = document.getElementById('applyForm');
    const formData = new FormData(form);
    const btn = document.getElementById('submitApplyBtn');
    const originalHTML = btn.innerHTML;
    btn.textContent = 'Submitting...';
    btn.disabled = true;

    fetch('/api/job-applications', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
      body: formData,
    })
    .then(async (response) => {
      const data = await response.json();
      if (data.success) {
        form.style.display = 'none';
        successBox.style.display = 'block';
        successBox.textContent = '✅ Application received! We\'ll be in touch soon.';
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

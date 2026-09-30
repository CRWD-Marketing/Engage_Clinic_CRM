@extends('layouts.landing')

@section('title', 'Engage Clinic · Contact')

@php
  $activePage = 'contact';
@endphp

@section('content')

<!-- Hero -->
<section class="js-hero py-20 md:py-28 text-center" style="background:var(--navy-deep);">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <span class="eyebrow-plain" style="color:var(--pink-mid);">Contact Us</span>
    <h1 class="display mt-4 text-3xl sm:text-5xl font-extrabold leading-[1.15] text-white">Let's start the conversation</h1>
    <p class="mt-5 text-white/75 text-base sm:text-lg leading-relaxed">Book a free 30-minute consultation. We'll listen, answer your questions, and help you find the right next step for your child.</p>
  </div>
</section>

<style>
  /* Preferred consultation: a date field that drops down the booking modal's
     month calendar (.cal-* styles), and a time dropdown. */
  .ct-date-field { position: relative; }
  .ct-date-btn { display: flex; align-items: center; justify-content: space-between; gap: 8px; text-align: left; cursor: pointer; color: var(--text); }
  .ct-date-btn svg { color: var(--text-muted); flex-shrink: 0; }
  .ct-date-btn[aria-expanded=true] { border-color: var(--pink); box-shadow: 0 0 0 3px rgba(200,53,95,.1); }
  .ct-placeholder { color: var(--text-muted); }
  .ct-cal { position: absolute; z-index: 40; top: calc(100% + 6px); left: 0; width: 310px; max-width: calc(100vw - 48px); padding: 14px; background: #fff; border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 18px 40px -12px rgba(22, 67, 110, .28); }
  .ct-cal[hidden] { display: none; }
  .ct-cal .cal-day { font-size: 12.5px; }
  .ct-cal .cal-legend { margin-top: 10px; gap: 12px; font-size: 11.5px; }
  .ct-cal .carousel-btn:disabled { opacity: .35; pointer-events: none; }
  select.crm-select:disabled { opacity: .6; cursor: not-allowed; }
  .ct-slot-note { margin-top: 8px; font-size: 12px; color: var(--text-muted); }
  .ct-slot-note.is-set { color: var(--navy); font-weight: 700; }
  .ct-slot-note button { margin-left: 6px; font-weight: 700; color: var(--pink); text-decoration: underline; }
</style>

<!-- Contact info + form -->
<section class="py-16 md:py-24 dotted">
  <div class="max-w-6xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

    <!-- Left: info -->
    <div>
      <h2 class="display text-3xl font-extrabold text-[var(--navy)] mb-8">We're here for you</h2>

      <div class="space-y-6">
        <div class="flex items-center gap-4">
          <div class="icon-box !rounded-full">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </div>
          <div>
            <p class="text-[11px] font-extrabold text-[var(--text-muted)] uppercase tracking-widest">Call Us</p>
            <a href="tel:+971508846801" class="font-bold text-[var(--navy)]">+971 50 884 6801</a>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="icon-box !rounded-full">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
          </div>
          <div>
            <p class="text-[11px] font-extrabold text-[var(--text-muted)] uppercase tracking-widest">Email Us</p>
            <a href="mailto:info@engagebehavior.com" class="font-bold text-[var(--navy)]">info@engagebehavior.com</a>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="icon-box !rounded-full">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div>
            <p class="text-[11px] font-extrabold text-[var(--text-muted)] uppercase tracking-widest">Visit Us</p>
            <p class="font-bold text-[var(--navy)]">Office no. 1203, ADCP Commercial Tower-C, Electra Street, Abu Dhabi, UAE</p>
          </div>
        </div>
      </div>

      <div class="card rounded-[26px] overflow-hidden aspect-[4/3] mt-10">
        <iframe
          src="https://www.google.com/maps?q=Office+no.+1203,+ADCP+Commercial+Tower-C,+Electra+Street,+Abu+Dhabi,+UAE&output=embed"
          class="w-full h-full border-0"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="Engage Clinic office location — ADCP Commercial Tower-C, Electra Street, Abu Dhabi">
        </iframe>
      </div>
    </div>

    <!-- Right: form -->
    <div class="card p-8 lg:p-10">
      <h3 class="display text-2xl font-extrabold text-[var(--navy)] mb-6">Send Us a Message</h3>

      <div id="contactSuccess" style="display:none;background:#E4F6EB;color:#1E8A4C;padding:12px 14px;border-radius:10px;margin-bottom:15px;font-size:13.5px;font-weight:600;border:1px solid #BFE9CE;"></div>
      <div id="contactError" style="display:none;background:#FEF2F2;color:#B91C1C;padding:12px 14px;border-radius:10px;margin-bottom:15px;font-size:13.5px;font-weight:600;border:1px solid #FECACA;"></div>

      <form id="contactForm" class="space-y-4" onsubmit="submitContact(event)">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="crm-label">Your Name *</label>
            <input type="text" id="ct_name" placeholder="Parent / Guardian" class="crm-input" required>
          </div>
          <div>
            <label class="crm-label">Child's Age</label>
            <input type="text" id="ct_child_age" placeholder="e.g. 4 years" class="crm-input">
          </div>
        </div>
        <div>
          <label class="crm-label">Child's Name</label>
          <input type="text" id="ct_child_name" placeholder="e.g. Hamad" class="crm-input">
        </div>
        <div>
          <label class="crm-label">Email Address</label>
          <input type="email" id="ct_email" placeholder="you@email.com" class="crm-input">
        </div>
        <div>
          <label class="crm-label">Phone Number</label>
          <input type="tel" id="ct_phone" placeholder="+971 5x xxx xxxx" class="crm-input" required>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="crm-label">Service of Interest</label>
            <select id="ct_interested_in" class="crm-select">
              <option value="">Select a program…</option>
              @foreach ($publicServices as $service)
                <option value="{{ $service->name }}">{{ $service->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="crm-label">Insurance</label>
            <select id="ct_insurance" class="crm-select">
              <option value="">Select your insurance…</option>
              @foreach ($publicInsurances as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <!-- Preferred consultation time - same days and slots as the Free Consultation booking. -->
        <div>
          <div class="flex items-baseline justify-between gap-2 mb-1">
            <span class="crm-label !mb-0">Preferred consultation</span>
            <span class="text-[11px] text-[var(--text-muted)]">Optional</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="ct-date-field">
              <label class="crm-label" for="ctDateBtn">Date</label>
              <button type="button" id="ctDateBtn" class="crm-select ct-date-btn" aria-haspopup="dialog" aria-expanded="false" aria-controls="ctCal">
                <span id="ctDateText" class="ct-placeholder">Select a date…</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              </button>
              <div class="ct-cal" id="ctCal" role="dialog" aria-label="Choose a date" hidden>
                <div class="cal-nav">
                  <button type="button" class="carousel-btn" id="ctCalPrev" aria-label="Previous month">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
                  </button>
                  <span id="ctCalLabel" class="display"></span>
                  <button type="button" class="carousel-btn" id="ctCalNext" aria-label="Next month">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
                  </button>
                </div>
                <div class="cal-weekdays">
                  <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>
                <div class="cal-grid" id="ctCalGrid"></div>
                <div class="cal-legend">
                  <span><i class="swatch today"></i> Today</span>
                  <span><i class="swatch unavailable"></i> Closed (Fri–Sat)</span>
                </div>
              </div>
            </div>
            <div>
              <label class="crm-label" for="ctTime">Time</label>
              <select id="ctTime" class="crm-select" disabled>
                <option value="">Pick a date first</option>
              </select>
            </div>
          </div>
          <p class="ct-slot-note" id="ctSlotNote">All times are Abu Dhabi time (GST). We're closed Fridays and Saturdays.</p>
        </div>
        <div>
          <label class="crm-label">Tell us about your child</label>
          <textarea id="ct_message" rows="4" placeholder="Any context about your child's needs, challenges, or goals…" class="crm-input" style="resize:vertical;"></textarea>
        </div>

        <!-- Consent gate: the submit button stays disabled until this is ticked. -->
        <label for="ct_consent" class="flex items-start gap-2.5 cursor-pointer select-none pt-1">
          <input type="checkbox" id="ct_consent" class="mt-[3px] w-4 h-4 shrink-0 cursor-pointer" style="accent-color:var(--pink);" required>
          <span class="text-[12.5px] leading-relaxed text-[var(--text-secondary)]">
            I agree to Engage Clinic contacting me about this enquiry and accept the
            <a href="{{ route('privacy-policy') }}" class="font-bold text-[var(--navy)] underline">Privacy Policy</a>.
          </span>
        </label>

        <button type="submit" class="btn-pink w-full justify-center" id="contactSubmitBtn" disabled>Send Message</button>
        <p class="text-center text-xs text-[var(--text-muted)]">We respond within 24 hours · Health insurance accepted</p>
      </form>
    </div>

  </div>
</section>

@include('landing_page.partials.booking-modal')

@endsection

@push('scripts')
<script>
  // ---- CSRF handling ----
  // Token is read at submit time, never baked into this script at render time,
  // so a page left open past SESSION_LIFETIME or served from cache can't post
  // a token the server has already rotated away from.

  function getXsrfCookie() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
  }

  function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return (meta && meta.content) ? meta.content : getXsrfCookie();
  }

  async function refreshCsrfToken() {
    try {
      const res = await fetch('{{ route('csrf.token') }}', {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!res.ok) return false;
      const data = await res.json();
      if (!data || !data.token) return false;

      let meta = document.querySelector('meta[name="csrf-token"]');
      if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('name', 'csrf-token');
        document.head.appendChild(meta);
      }
      meta.setAttribute('content', data.token);
      return true;
    } catch (e) {
      return false;
    }
  }

  async function postJson(url, payload, allowRetry = true) {
    const headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': getCsrfToken(),
    };
    const xsrf = getXsrfCookie();
    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: headers,
      body: JSON.stringify(payload),
    });

    if (response.status === 419 && allowRetry) {
      const refreshed = await refreshCsrfToken();
      if (refreshed) return postJson(url, payload, false);
    }

    // 419/500 responses are HTML, so an unconditional .json() would throw and
    // surface the real failure as a misleading "Network error".
    let data = null;
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
      data = await response.json().catch(() => null);
    }

    return { ok: response.ok, status: response.status, data: data };
  }

  // ---- Consent gate ----
  // The Send Message button ships disabled in the markup and only unlocks once
  // the consent box is ticked. Synced on load as well as on change, because
  // browsers restore checkbox state on reload and back-navigation - without the
  // initial sync the box could come back ticked with the button still dead.
  const ctConsent = document.getElementById('ct_consent');
  const ctSubmitBtn = document.getElementById('contactSubmitBtn');

  function syncConsentGate() {
    if (ctConsent && ctSubmitBtn) ctSubmitBtn.disabled = !ctConsent.checked;
  }

  if (ctConsent) {
    ctConsent.addEventListener('change', syncConsentGate);
    syncConsentGate();
  }
  window.addEventListener('pageshow', syncConsentGate);

  // ---- Preferred consultation (date + time) ----
  // Date: a collapsed field that drops down the booking modal's month calendar
  // (past days and Fri–Sat closed). Time: a dropdown of the booking modal's
  // TIME_SLOTS. Optional - nothing picked sends a plain message; a date
  // without a time is caught before sending.
  const ctSlot = { date: null, time: null, monthOffset: 0 };
  const ctDateBtn = document.getElementById('ctDateBtn');
  const ctCal = document.getElementById('ctCal');
  const ctTime = document.getElementById('ctTime');

  function setCtCalOpen(open) {
    ctCal.hidden = !open;
    ctDateBtn.setAttribute('aria-expanded', open);
    if (open) {
      // Open on the chosen date's month, or the current one.
      const now = new Date();
      ctSlot.monthOffset = ctSlot.date ? (ctSlot.date.getFullYear() - now.getFullYear()) * 12 + ctSlot.date.getMonth() - now.getMonth() : 0;
      renderCtCalendar();
    }
  }
  ctDateBtn.addEventListener('click', () => setCtCalOpen(ctCal.hidden));
  document.addEventListener('click', (e) => { if (!ctCal.hidden && !e.target.closest('.ct-date-field')) setCtCalOpen(false); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !ctCal.hidden) { setCtCalOpen(false); ctDateBtn.focus(); } });

  function renderCtCalendar() {
    const base = new Date();
    base.setDate(1);
    base.setMonth(base.getMonth() + ctSlot.monthOffset);
    document.getElementById('ctCalLabel').textContent = base.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
    document.getElementById('ctCalPrev').disabled = ctSlot.monthOffset <= 0;

    const today = new Date(); today.setHours(0, 0, 0, 0);
    const year = base.getFullYear(), month = base.getMonth();
    const grid = document.getElementById('ctCalGrid');
    grid.innerHTML = '';
    for (let i = 0; i < new Date(year, month, 1).getDay(); i++) {
      const pad = document.createElement('div');
      pad.className = 'cal-day faded';
      grid.appendChild(pad);
    }
    for (let d = 1; d <= new Date(year, month + 1, 0).getDate(); d++) {
      const day = new Date(year, month, d);
      const cell = document.createElement('button');
      cell.type = 'button';
      cell.className = 'cal-day';
      cell.textContent = d;
      const closed = day < today || day.getDay() === 5 || day.getDay() === 6;
      const on = ctSlot.date && day.getTime() === ctSlot.date.getTime();
      if (closed) { cell.classList.add('disabled'); cell.disabled = true; }
      if (day.getTime() === today.getTime()) cell.classList.add('today');
      if (on) cell.classList.add('selected');
      cell.setAttribute('aria-label', day.toLocaleDateString('en-GB', WEEKDAY_FMT) + (closed ? ' (unavailable)' : ''));
      if (!closed) cell.addEventListener('click', () => {
        ctSlot.date = day;
        setCtCalOpen(false);
        renderCtSlots();
        ctTime.focus();
      });
      grid.appendChild(cell);
    }
  }
  document.getElementById('ctCalPrev').addEventListener('click', () => { ctSlot.monthOffset = Math.max(0, ctSlot.monthOffset - 1); renderCtCalendar(); });
  document.getElementById('ctCalNext').addEventListener('click', () => { ctSlot.monthOffset++; renderCtCalendar(); });

  ctTime.addEventListener('change', () => { ctSlot.time = ctTime.value || null; renderCtNote(); });

  function renderCtSlots() {
    const dateText = document.getElementById('ctDateText');
    dateText.textContent = ctSlot.date ? ctSlot.date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) : 'Select a date…';
    dateText.classList.toggle('ct-placeholder', !ctSlot.date);

    ctTime.disabled = !ctSlot.date;
    ctTime.innerHTML = '';
    ctTime.add(new Option(ctSlot.date ? 'Select a time…' : 'Pick a date first', ''));
    if (ctSlot.date) TIME_SLOTS.forEach(t => ctTime.add(new Option(t, t, false, t === ctSlot.time)));
    renderCtNote();
  }

  function renderCtNote() {
    const note = document.getElementById('ctSlotNote');
    note.classList.toggle('is-set', !!(ctSlot.date && ctSlot.time));
    if (ctSlot.date && ctSlot.time) {
      note.innerHTML = `Requested: ${ctSlot.date.toLocaleDateString('en-GB', WEEKDAY_FMT)} at ${ctSlot.time} · 30-min free consultation <button type="button" id="ctSlotClear">Clear</button>`;
      document.getElementById('ctSlotClear').addEventListener('click', () => { ctSlot.date = null; ctSlot.time = null; renderCtSlots(); });
    } else if (ctSlot.date) {
      note.textContent = 'Now choose a time (Abu Dhabi time).';
    } else {
      note.textContent = "All times are Abu Dhabi time (GST). We're closed Fridays and Saturdays.";
    }
  }
  renderCtSlots();

  // ---- Inline contact form ----
  async function submitContact(event) {
    event.preventDefault();

    const successBox = document.getElementById('contactSuccess');
    const errorBox = document.getElementById('contactError');
    successBox.style.display = 'none';
    errorBox.style.display = 'none';

    // Server-side validation is the real gate; this is the UI half of it.
    if (ctConsent && !ctConsent.checked) {
      errorBox.style.display = 'block';
      errorBox.textContent = 'Tick the consent box before sending your message.';
      syncConsentGate();
      return;
    }

    const childName = document.getElementById('ct_child_name').value.trim();
    const childAge = document.getElementById('ct_child_age').value.trim();
    const name = document.getElementById('ct_name').value.trim();
    const phone = document.getElementById('ct_phone').value.trim();
    const email = document.getElementById('ct_email').value.trim();
    const interestedIn = document.getElementById('ct_interested_in').value;
    const insurance = document.getElementById('ct_insurance').value;
    const message = document.getElementById('ct_message').value.trim();

    if (ctSlot.date && !ctSlot.time) {
      errorBox.style.display = 'block';
      errorBox.textContent = 'Choose a time for your consultation, or clear the date you picked.';
      ctTime.focus();
      return;
    }
    const slotText = ctSlot.date ? `${ctSlot.date.toLocaleDateString('en-GB', WEEKDAY_FMT)} at ${ctSlot.time}` : null;

    const btn = ctSubmitBtn;
    const originalText = btn.textContent;
    btn.textContent = 'Sending...';
    btn.disabled = true;

    try {
      const result = await postJson('/api/contacts', {
        child_name: childName,
        child_age: childAge,
        name: name,
        email: email,
        phone: phone,
        interested_in: interestedIn,
        insurance: insurance,
        // Same shape as a booking, so the slot shows in CRM -> Contacts.
        message: slotText ? `Preferred consultation: ${slotText} (30-min free consultation)` + (message ? `\n${message}` : '') : message,
        booking_date: ctSlot.date ? bookingIsoDate(ctSlot.date) : null,
        booking_time: ctSlot.date ? ctSlot.time : null,
      });

      if (result.ok && result.data && result.data.success) {
        document.getElementById('contactForm').style.display = 'none';
        successBox.style.display = 'block';
        successBox.textContent = slotText
          ? `Message received — we'll confirm your consultation on ${slotText} within 24 hours.`
          : "Message received — we'll be in touch within 24 hours.";
        return;
      }

      errorBox.style.display = 'block';
      if (result.status === 419) {
        errorBox.textContent = 'Your session expired. Refresh the page and send again.';
      } else if (result.status === 429) {
        errorBox.textContent = 'Too many attempts. Wait a moment and try again.';
      } else if (result.data && result.data.errors) {
        errorBox.textContent = Object.values(result.data.errors).flat().join(', ');
      } else if (result.data && result.data.message) {
        errorBox.textContent = result.data.message;
      } else {
        errorBox.textContent = `Something went wrong (error ${result.status}). Try again, or call +971 50 884 6801.`;
      }
    } catch (e) {
      errorBox.style.display = 'block';
      errorBox.textContent = 'Network error. Check your connection and try again.';
    } finally {
      btn.textContent = originalText;
      // Re-enable only if consent is still ticked, so the gate survives a retry.
      btn.disabled = ctConsent ? !ctConsent.checked : false;
    }
  }

</script>
@endpush
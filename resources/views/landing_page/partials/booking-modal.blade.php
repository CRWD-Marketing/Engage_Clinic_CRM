{{--
  "Book a Free Consultation" modal - shared by every landing page that has a
  Free Consultation button (openBookingModal()). Needs $publicServices and
  $publicInsurances (AppServiceProvider view composer). Posts to /api/contacts,
  so each booking lands in CRM -> Contacts for staff to confirm.
--}}
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
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
              <label class="crm-label">Insurance</label>
              <select id="bk_insurance" class="crm-select">
                <option value="">Select your insurance…</option>
                @foreach ($publicInsurances as $value => $label)
                  <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
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

<script>
  // Consultation times (Abu Dhabi time) - also used by the Contact page's
  // "preferred time" picker, so both offer the same slots.
  const TIME_SLOTS = @json(\App\Models\Contact::CONSULTATION_TIMES);
  const WEEKDAY_FMT = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
  const bookingState = { date: null, time: null, monthOffset: 0 };

  // Y-M-D in local time, not toISOString() (which would shift the date near
  // midnight for GST/UTC+4 users) - what the admin list sorts and displays.
  function bookingIsoDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  }

  // ---- CSRF-safe JSON post ----
  // The token is read at submit time (never baked in at render time), and a
  // 419 from an expired page is retried once with a fresh token.
  function bookingXsrfCookie() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
  }
  function bookingCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return (meta && meta.content) ? meta.content : bookingXsrfCookie();
  }
  async function bookingRefreshCsrf() {
    try {
      const res = await fetch(@json(route('csrf.token')), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
      if (!res.ok) return false;
      const data = await res.json();
      if (!data || !data.token) return false;
      let meta = document.querySelector('meta[name="csrf-token"]');
      if (!meta) { meta = document.createElement('meta'); meta.setAttribute('name', 'csrf-token'); document.head.appendChild(meta); }
      meta.setAttribute('content', data.token);
      return true;
    } catch (e) { return false; }
  }
  async function bookingPostJson(url, payload, allowRetry = true) {
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': bookingCsrfToken() };
    const xsrf = bookingXsrfCookie();
    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
    const response = await fetch(url, { method: 'POST', credentials: 'same-origin', headers, body: JSON.stringify(payload) });
    if (response.status === 419 && allowRetry && await bookingRefreshCsrf()) return bookingPostJson(url, payload, false);
    // 419/500 responses are HTML - only parse JSON when that's what came back.
    let data = null;
    if ((response.headers.get('content-type') || '').includes('application/json')) data = await response.json().catch(() => null);
    return { ok: response.ok, status: response.status, data };
  }
  function bookingErrorText(result) {
    if (result.status === 419) return 'Your session expired. Refresh the page and submit again.';
    if (result.status === 429) return 'Too many attempts. Wait a moment and try again.';
    if (result.data && result.data.errors) return Object.values(result.data.errors).flat().join(', ');
    if (result.data && result.data.message) return result.data.message;
    return `Something went wrong (error ${result.status}). Try again, or call +971 50 884 6801.`;
  }

  // ---- Modal ----
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

  async function submitBooking(event) {
    event.preventDefault();

    const childName = document.getElementById('bk_child_name').value.trim();
    const childAge = document.getElementById('bk_child_age').value.trim();
    const name = document.getElementById('bk_name').value.trim();
    const phone = document.getElementById('bk_phone').value.trim();
    const email = document.getElementById('bk_email').value.trim();
    const service = document.getElementById('bk_service').value;
    const insurance = document.getElementById('bk_insurance').value;
    const notes = document.getElementById('bk_notes').value.trim();
    const successBox = document.getElementById('bookingSuccess');
    const errorBox = document.getElementById('bookingError');
    successBox.style.display = 'none';
    errorBox.style.display = 'none';

    if (!bookingState.date || !bookingState.time) {
      errorBox.style.display = 'block';
      errorBox.textContent = 'Pick a date and time before confirming.';
      return;
    }

    const dateStr = bookingState.date.toLocaleDateString('en-GB', WEEKDAY_FMT);
    // A booking request is an enquiry, not a qualified lead yet - it goes to
    // Contacts like every other website submission, for a staff member to
    // review and manually convert. The message keeps the slot and whatever
    // the family wrote.
    const combinedNotes = `Booking request: ${dateStr} at ${bookingState.time} (30-min free consultation)` + (notes ? `\n${notes}` : '');

    const btn = document.getElementById('confirmBookingBtn');
    const originalHTML = btn.innerHTML;
    btn.textContent = 'Booking...';
    btn.disabled = true;

    try {
      const result = await bookingPostJson('/api/contacts', {
        name: name,
        child_name: childName,
        child_age: childAge,
        email: email,
        phone: phone,
        interested_in: service,
        insurance: insurance,
        message: combinedNotes,
        booking_date: bookingIsoDate(bookingState.date),
        booking_time: bookingState.time,
      });

      if (result.ok && result.data && result.data.success) {
        document.getElementById('bookingForm').style.display = 'none';
        successBox.style.display = 'block';
        successBox.textContent = `Booking request received for ${dateStr} at ${bookingState.time}. We'll confirm within 24 hours.`;
        return;
      }
      errorBox.style.display = 'block';
      errorBox.textContent = bookingErrorText(result);
    } catch (e) {
      errorBox.style.display = 'block';
      errorBox.textContent = 'Network error. Check your connection and try again.';
    } finally {
      btn.innerHTML = originalHTML;
      btn.disabled = false;
    }
  }
</script>

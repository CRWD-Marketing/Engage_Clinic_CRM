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

@include('landing_page.partials.booking-modal')

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

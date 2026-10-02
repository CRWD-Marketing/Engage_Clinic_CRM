@extends('layouts.landing')

@section('title', 'Engage Clinic · Vendor Registration')

@php
  $activePage = 'vendor-registration';
  $sections = [
    'company' => 'Company details',
    'contact' => 'Contact person',
    'services' => 'Category & services',
    'documents' => 'Compliance documents',
    'commercial' => 'Commercial terms',
    'bank' => 'Bank details',
    'declaration' => 'Declaration',
  ];
@endphp

@section('content')

<div class="vr">

  <!-- Page header -->
  <section class="vr-head">
    <div class="vr-wrap">
      <span class="vr-badge vr-badge-outline">Supplier &amp; partner onboarding</span>
      <h1 class="display">Become an <span>Engage</span> vendor</h1>
      <p class="vr-lead">Register your company to supply goods or services to Engage Behavioral Development Clinic LLC. Our Operations team reviews every application, verifies your documents and contacts you about next steps.</p>
      <div class="vr-chips">
        <span class="vr-badge vr-badge-secondary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          About 10 minutes
        </span>
        <span class="vr-badge vr-badge-secondary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          Trade licence &amp; VAT certificate needed
        </span>
        <span class="vr-badge vr-badge-secondary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
          PDF, JPG or PNG up to {{ $maxFileMb }} MB
        </span>
      </div>
    </div>
  </section>

  <div class="vr-wrap vr-main">

    <!-- Section index -->
    <nav class="vr-index" aria-label="Form sections">
      <ol>
        @foreach ($sections as $id => $label)
          <li><a href="#{{ $id }}" data-s="{{ $id }}"><span class="n">{{ $loop->iteration }}</span>{{ $label }}</a></li>
        @endforeach
      </ol>
      <div class="vr-progress">
        <div class="vr-progress-row"><span>Progress</span><span id="vrProgressText">0 of 7</span></div>
        <div class="vr-bar"><i id="vrProgressBar"></i></div>
      </div>
    </nav>

    <div class="vr-col">
      <form id="vendorForm" class="vr-form" novalidate>
        {{-- Honeypot - hidden from people, filled in by bots. --}}
        <div class="vr-hp" aria-hidden="true">
          <label>Company fax <input type="text" name="company_fax" tabindex="-1" autocomplete="off"></label>
        </div>

        <!-- 1. Company -->
        <section class="vr-card" id="company">
          <header class="vr-card-head">
            <span class="vr-step">1</span>
            <div><h2>Company details</h2><p>Use the legal name exactly as it appears on your trade licence.</p></div>
          </header>
          <div class="vr-card-body vr-grid">
            <div class="vr-field vr-full">
              <label class="vr-label" for="legal_name">Registered company name <span class="req">*</span></label>
              <input class="vr-input" id="legal_name" name="legal_name" required autocomplete="organization" placeholder="e.g. Al Noor Facility Services LLC">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="trade_name">Trade name <small>if different</small></label>
              <input class="vr-input" id="trade_name" name="trade_name">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="website">Website</label>
              <input class="vr-input" id="website" name="website" type="url" placeholder="https://">
            </div>
            <div class="vr-field vr-full">
              <label class="vr-label" for="address">Registered or operating address <span class="req">*</span></label>
              <input class="vr-input" id="address" name="address" required placeholder="Office, building, street">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="city">City <span class="req">*</span></label>
              <input class="vr-input" id="city" name="city" required>
            </div>
            <div class="vr-field">
              <label class="vr-label" for="emirate">Emirate <span class="req">*</span></label>
              <select class="vr-input vr-select" id="emirate" name="emirate" required>
                <option value="">Select emirate</option>
                @foreach ($emirates as $emirate)<option>{{ $emirate }}</option>@endforeach
              </select>
            </div>
          </div>
        </section>

        <!-- 2. Contact -->
        <section class="vr-card" id="contact">
          <header class="vr-card-head">
            <span class="vr-step">2</span>
            <div><h2>Contact person</h2><p>The person our team will contact about orders, documents and reviews.</p></div>
          </header>
          <div class="vr-card-body vr-grid">
            <div class="vr-field">
              <label class="vr-label" for="contact_name">Full name <span class="req">*</span></label>
              <input class="vr-input" id="contact_name" name="contact_name" required autocomplete="name">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="position">Position <span class="req">*</span></label>
              <input class="vr-input" id="position" name="position" required placeholder="e.g. Sales Manager">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="phone">Mobile number <span class="req">*</span></label>
              <input class="vr-input" id="phone" name="phone" type="tel" required autocomplete="tel" placeholder="+971 50 123 4567">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="email">Email <span class="req">*</span></label>
              <input class="vr-input" id="email" name="email" type="email" required autocomplete="email" placeholder="name@company.ae">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="alt_contact">Alternate contact <small>name &amp; number, optional</small></label>
              <input class="vr-input" id="alt_contact" name="alt_contact">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="finance_email">Accounts / finance email</label>
              <input class="vr-input" id="finance_email" name="finance_email" type="email">
            </div>
          </div>
        </section>

        <!-- 3. Category & services -->
        <section class="vr-card" id="services">
          <header class="vr-card-head">
            <span class="vr-step">3</span>
            <div><h2>Category &amp; services</h2><p>Pick the category that best describes what you will supply to the clinic.</p></div>
          </header>
          <div class="vr-card-body vr-grid">
            <div class="vr-field vr-full" role="radiogroup" aria-labelledby="vrCatLabel" data-group="category">
              <span class="vr-label" id="vrCatLabel">Vendor category <span class="req">*</span></span>
              <div class="vr-toggles">
                @foreach ($categories as $category)
                  <label class="vr-toggle"><input type="radio" name="category" value="{{ $category }}"><span>{{ $category }}</span></label>
                @endforeach
              </div>
            </div>
            <div class="vr-field vr-full" id="vrOtherWrap" hidden>
              <label class="vr-label" for="category_other">Describe the category <span class="req">*</span></label>
              <input class="vr-input" id="category_other" name="category_other">
            </div>
            <div class="vr-field vr-full">
              <label class="vr-label" for="primary_service">Primary service or product <span class="req">*</span></label>
              <input class="vr-input" id="primary_service" name="primary_service" required placeholder="e.g. Daily cleaning and infection-control services">
            </div>
            <div class="vr-field vr-full">
              <label class="vr-label" for="description">Brief description of your offer</label>
              <textarea class="vr-input vr-textarea" id="description" name="description" placeholder="What you supply, service coverage, response times, relevant healthcare or education clients"></textarea>
            </div>
            <div class="vr-field">
              <label class="vr-label" for="years_in_business">Years in business</label>
              <input class="vr-input" id="years_in_business" name="years_in_business" type="number" min="0" inputmode="numeric">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="referred_by">Referred by <small>Engage staff member, if any</small></label>
              <input class="vr-input" id="referred_by" name="referred_by">
            </div>
          </div>
        </section>

        <!-- 4. Documents -->
        <section class="vr-card" id="documents">
          <header class="vr-card-head">
            <span class="vr-step">4</span>
            <div><h2>Compliance documents</h2><p>Upload a clear copy of each document. We record the number, issue and expiry dates so we can remind you before anything lapses.</p></div>
          </header>
          <div class="vr-card-body">
            <div class="vr-docs">
              @foreach ($documentTypes as $type => $doc)
                <div class="vr-doc" data-doc="{{ $type }}" data-expires="{{ $doc['expires'] ? '1' : '0' }}">
                  <div class="vr-doc-head">
                    <div>
                      <b>{{ $doc['label'] }}</b>@if ($type === 'trade_licence') <span class="req">*</span>@endif
                      @if ($doc['hint'])<em>{{ $doc['hint'] }}</em>@endif
                    </div>
                    <span class="vr-badge vr-badge-secondary" data-doc-status>Not uploaded</span>
                  </div>
                  <div class="vr-doc-grid">
                    <div class="vr-field">
                      <label class="vr-label" for="doc_{{ $type }}_file">File</label>
                      <input class="vr-input vr-file" id="doc_{{ $type }}_file" name="documents[{{ $type }}][file]" type="file" accept=".pdf,.jpg,.jpeg,.png" @if ($type === 'trade_licence') required @endif>
                    </div>
                    <div class="vr-field">
                      <label class="vr-label" for="doc_{{ $type }}_number">Document number</label>
                      <input class="vr-input" id="doc_{{ $type }}_number" name="documents[{{ $type }}][number]">
                    </div>
                    <div class="vr-field">
                      <label class="vr-label" for="doc_{{ $type }}_issue">Issue date</label>
                      <input class="vr-input" id="doc_{{ $type }}_issue" name="documents[{{ $type }}][issue_date]" type="date">
                    </div>
                    <div class="vr-field">
                      <label class="vr-label" for="doc_{{ $type }}_expiry">Expiry date</label>
                      <input class="vr-input" id="doc_{{ $type }}_expiry" name="documents[{{ $type }}][expiry_date]" type="date">
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
            <div class="vr-alert">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
              <p>Documents that expire within 30 days are flagged for renewal. Fire and life safety, security and CCTV, medical waste, cleaning, facility maintenance, IT systems and clinical supply vendors are reviewed as critical vendors and may be asked for extra documents.</p>
            </div>
          </div>
        </section>

        <!-- 5. Commercial -->
        <section class="vr-card" id="commercial">
          <header class="vr-card-head">
            <span class="vr-step">5</span>
            <div><h2>Commercial terms</h2><p>Your proposed terms. Final terms are confirmed in the agreement or purchase order.</p></div>
          </header>
          <div class="vr-card-body vr-grid">
            <div class="vr-field">
              <label class="vr-label" for="pricing">Pricing / rate <span class="req">*</span></label>
              <input class="vr-input" id="pricing" name="pricing" required placeholder="e.g. AED 3,500 per month">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="monthly_cost">Estimated monthly cost <small>AED</small></label>
              <input class="vr-input" id="monthly_cost" name="monthly_cost" type="number" min="0" step="0.01" inputmode="decimal">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="payment_terms">Payment terms <span class="req">*</span></label>
              <select class="vr-input vr-select" id="payment_terms" name="payment_terms" required>
                <option value="">Select terms</option>
                @foreach ($paymentTerms as $term)<option>{{ $term }}</option>@endforeach
              </select>
            </div>
            <div class="vr-field">
              <label class="vr-label" for="payment_method">Preferred payment method <span class="req">*</span></label>
              <select class="vr-input vr-select" id="payment_method" name="payment_method" required>
                <option value="">Select method</option>
                @foreach ($paymentMethods as $method)<option>{{ $method }}</option>@endforeach
              </select>
            </div>
            <div class="vr-field" role="radiogroup" aria-labelledby="vrVatLabel" data-group="vat_registered">
              <span class="vr-label" id="vrVatLabel">VAT registered? <span class="req">*</span></span>
              <div class="vr-seg">
                <label><input type="radio" name="vat_registered" value="Yes"><span>Yes</span></label>
                <label><input type="radio" name="vat_registered" value="No"><span>No</span></label>
              </div>
            </div>
            <div class="vr-field" id="vrTrnWrap" hidden>
              <label class="vr-label" for="trn">Tax Registration Number (TRN) <span class="req">*</span></label>
              <input class="vr-input" id="trn" name="trn" inputmode="numeric" maxlength="15" placeholder="15 digits">
            </div>
            <div class="vr-field" role="radiogroup" aria-labelledby="vrPoLabel">
              <span class="vr-label" id="vrPoLabel">Can you work against purchase orders?</span>
              <div class="vr-seg">
                <label><input type="radio" name="accepts_po" value="Yes"><span>Yes</span></label>
                <label><input type="radio" name="accepts_po" value="No"><span>No</span></label>
              </div>
            </div>
            <div class="vr-field">
              <label class="vr-label" for="contract_start">Proposed contract start</label>
              <input class="vr-input" id="contract_start" name="contract_start" type="date">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="contract_end">Proposed contract end</label>
              <input class="vr-input" id="contract_end" name="contract_end" type="date">
            </div>
          </div>
        </section>

        <!-- 6. Bank -->
        <section class="vr-card" id="bank">
          <header class="vr-card-head">
            <span class="vr-step">6</span>
            <div><h2>Bank details</h2><p>Used for payments only. Our Finance team verifies these against your bank letter before the first payment.</p></div>
          </header>
          <div class="vr-card-body vr-grid">
            <div class="vr-field">
              <label class="vr-label" for="bank_name">Bank name <span class="req">*</span></label>
              <input class="vr-input" id="bank_name" name="bank_name" required>
            </div>
            <div class="vr-field">
              <label class="vr-label" for="account_holder">Account holder name <span class="req">*</span></label>
              <input class="vr-input" id="account_holder" name="account_holder" required>
            </div>
            <div class="vr-field">
              <label class="vr-label" for="iban">IBAN <span class="req">*</span></label>
              <input class="vr-input" id="iban" name="iban" required placeholder="AE07 0331 2345 6789 0123 456" autocomplete="off">
            </div>
            <div class="vr-field">
              <label class="vr-label" for="swift">SWIFT / BIC</label>
              <input class="vr-input" id="swift" name="swift" maxlength="11" placeholder="e.g. NBADAEAA">
            </div>
            <div class="vr-field vr-full">
              <label class="vr-label" for="bank_letter">Bank letter or cancelled cheque <span class="req">*</span></label>
              <input class="vr-input vr-file" id="bank_letter" name="bank_letter" type="file" required accept=".pdf,.jpg,.jpeg,.png">
            </div>
          </div>
        </section>

        <!-- 7. Declaration -->
        <section class="vr-card" id="declaration">
          <header class="vr-card-head">
            <span class="vr-step">7</span>
            <div><h2>Declaration</h2><p>Engage works with children and families, so we hold every vendor to high standards of conduct and confidentiality.</p></div>
          </header>
          <div class="vr-card-body vr-grid">
            <label class="vr-check vr-full"><input type="checkbox" name="declaration_accurate" value="1" required><span>The information and documents in this form are accurate and current. I will tell Engage about any change, including licence renewals.</span></label>
            <label class="vr-check vr-full"><input type="checkbox" name="declaration_confidential" value="1" required><span>Our staff will keep any client, patient or clinic information confidential and will sign an NDA if requested.</span></label>
            <label class="vr-check vr-full"><input type="checkbox" name="declaration_review" value="1" required><span>I understand that Engage reviews vendor performance periodically and may place a vendor on hold, suspend or end the relationship for compliance, quality or performance reasons.</span></label>
            <div class="vr-field">
              <label class="vr-label" for="signatory_name">Authorised signatory name <span class="req">*</span></label>
              <input class="vr-input" id="signatory_name" name="signatory_name" required>
            </div>
            <div class="vr-field">
              <label class="vr-label" for="vrSignDate">Date</label>
              <input class="vr-input" id="vrSignDate" type="date" readonly tabindex="-1">
            </div>
          </div>
        </section>

        <div class="vr-actions">
          <span class="vr-form-error" id="vrFormError" role="alert"></span>
          <button class="vr-btn" type="submit" id="vrSubmit">Submit registration</button>
        </div>
      </form>

      <!-- Success -->
      <section class="vr-card vr-done" id="vrDone" hidden>
        <div class="vr-tick"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
        <h2 class="display">Registration received</h2>
        <p id="vrDoneMsg">Thank you. Our Operations team will review your documents and contact you.</p>
        <div class="vr-ref" id="vrRef">ENG-VND-0000</div>
        <p>Keep this reference for any follow-up with our team.</p>
        <div class="vr-steps">
          <div><b>Document check</b>Operations verifies your licence, VAT and insurance.</div>
          <div><b>Review &amp; approval</b>Due diligence and management approval.</div>
          <div><b>Activation</b>You are set up as an active vendor and assigned an Engage contact.</div>
        </div>
        <button class="vr-btn vr-btn-outline" type="button" id="vrAgain">Register another vendor</button>
      </section>
    </div>
  </div>
</div>

@include('landing_page.partials.booking-modal')

@endsection

@push('head')
<style>
  /* shadcn/ui-style tokens (zinc neutrals, thin borders, small radii, ring
     focus) with the Engage pink as primary. Scoped under .vr so nothing
     here leaks into the shared landing layout. */
  .vr{
    --background:#ffffff; --foreground:#09090b;
    --card:#ffffff; --muted:#f4f4f5; --muted-foreground:#71717a;
    --border:#e4e4e7; --input:#e4e4e7;
    --primary:#C8355F; --primary-hover:#A82348; --primary-soft:#FCEAF0;
    --ring:rgba(200,53,95,.3);
    --destructive:#dc2626; --destructive-soft:#fef2f2;
    --success:#15803d; --success-soft:#f0fdf4; --success-border:#bbf7d0;
    --warning:#a16207; --warning-soft:#fefce8; --warning-border:#fde68a;
    --radius:10px; --radius-sm:8px;
    --shadow-sm:0 1px 2px 0 rgba(9,9,11,.05);
    background:#fafafa; color:var(--foreground); font-size:14px; line-height:1.5;
  }
  .vr [hidden]{display:none !important;}
  .vr-wrap{max-width:1120px;margin:0 auto;padding-inline:24px;}

  /* Header */
  .vr-head{background:var(--background);border-bottom:1px solid var(--border);}
  .vr-head .vr-wrap{padding-block:48px 40px;}
  .vr-head h1{font-size:clamp(30px,4.6vw,44px);font-weight:800;line-height:1.1;letter-spacing:-.01em;color:var(--navy);margin:14px 0 10px;text-wrap:balance;}
  .vr-head h1 span{color:var(--primary);}
  .vr-lead{max-width:64ch;color:var(--muted-foreground);font-size:15.5px;line-height:1.65;}
  .vr-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px;}

  .vr-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;border:1px solid transparent;padding:3px 10px;font-size:12px;font-weight:600;line-height:1.5;white-space:nowrap;}
  .vr-badge-outline{border-color:var(--border);color:var(--foreground);background:var(--background);}
  .vr-badge-secondary{background:var(--muted);color:#3f3f46;}
  .vr-badge.is-ok{background:var(--success-soft);color:var(--success);border-color:var(--success-border);}
  .vr-badge.is-warn{background:var(--warning-soft);color:var(--warning);border-color:var(--warning-border);}
  .vr-badge.is-bad{background:var(--destructive-soft);color:#b91c1c;border-color:#fecaca;}

  /* Layout */
  .vr-main{display:grid;grid-template-columns:232px minmax(0,1fr);gap:32px;padding-block:32px 72px;}
  .vr-index{position:sticky;top:104px;align-self:start;}
  .vr-index ol{list-style:none;margin:0;padding:0;display:grid;gap:2px;}
  .vr-index a{display:flex;align-items:center;gap:10px;padding:7px 10px;border-radius:var(--radius-sm);font-size:13.5px;font-weight:500;color:var(--muted-foreground);transition:background-color .15s,color .15s;}
  .vr-index a:hover{background:var(--muted);color:var(--foreground);}
  .vr-index a .n{flex:none;width:22px;height:22px;border-radius:999px;border:1px solid var(--border);background:var(--background);display:grid;place-items:center;font-size:11px;font-weight:600;font-variant-numeric:tabular-nums;}
  .vr-index a.done{color:var(--foreground);}
  .vr-index a.done .n{background:var(--primary);border-color:var(--primary);color:#fff;}
  .vr-progress{margin-top:16px;padding:14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);box-shadow:var(--shadow-sm);}
  .vr-progress-row{display:flex;justify-content:space-between;font-size:12.5px;font-weight:500;color:var(--muted-foreground);}
  .vr-progress-row span:last-child{color:var(--foreground);font-variant-numeric:tabular-nums;}
  .vr-bar{height:6px;border-radius:999px;background:var(--muted);margin-top:8px;overflow:hidden;}
  .vr-bar i{display:block;height:100%;width:0;background:var(--primary);border-radius:999px;transition:width .3s;}

  /* Cards */
  .vr-form{display:grid;gap:20px;}
  .vr-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
  .vr-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-sm);scroll-margin-top:100px;}
  .vr-card-head{display:flex;gap:14px;align-items:flex-start;padding:22px 24px 0;}
  .vr-card-head h2{font-size:17px;font-weight:600;letter-spacing:-.01em;line-height:1.3;}
  .vr-card-head p{margin-top:3px;color:var(--muted-foreground);font-size:13.5px;max-width:62ch;}
  .vr-step{flex:none;width:28px;height:28px;border-radius:var(--radius-sm);background:var(--primary-soft);color:var(--primary);display:grid;place-items:center;font-size:13px;font-weight:700;}
  .vr-card-body{padding:20px 24px 24px;}
  .vr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;}
  .vr-full{grid-column:1/-1;}

  /* Fields */
  .vr-field{display:flex;flex-direction:column;gap:6px;min-width:0;}
  .vr-label{font-size:13.5px;font-weight:500;line-height:1.3;color:var(--foreground);}
  .vr-label small{margin-left:4px;font-weight:400;font-size:12.5px;color:var(--muted-foreground);}
  .vr .req{color:var(--primary);}
  .vr-input{width:100%;min-width:0;height:38px;padding:0 12px;border:1px solid var(--input);border-radius:var(--radius-sm);background:var(--background);color:var(--foreground);font:inherit;font-size:14px;box-shadow:var(--shadow-sm);transition:border-color .15s,box-shadow .15s;outline:none;}
  .vr-input::placeholder{color:#a1a1aa;}
  .vr-input:focus-visible{border-color:var(--primary);box-shadow:0 0 0 3px var(--ring);}
  .vr-input[readonly]{background:var(--muted);color:var(--muted-foreground);}
  .vr-textarea{height:auto;min-height:92px;padding:9px 12px;resize:vertical;line-height:1.5;}
  .vr-select{appearance:none;padding-right:34px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
  .vr-file{padding:0 12px 0 0;font-size:13px;color:var(--muted-foreground);line-height:36px;overflow:hidden;cursor:pointer;}
  .vr-file::file-selector-button{height:36px;margin-right:12px;padding:0 12px;border:0;border-right:1px solid var(--input);background:var(--muted);color:var(--foreground);font:inherit;font-size:13px;font-weight:500;cursor:pointer;}
  .vr-file:hover::file-selector-button{background:#e9e9eb;}
  .vr-input.is-invalid,.vr-invalid .vr-seg,.vr-invalid .vr-toggle span{border-color:var(--destructive);}
  .vr-error{font-size:12.5px;font-weight:500;color:var(--destructive);}

  /* Toggle group (category) */
  .vr-toggles{display:flex;flex-wrap:wrap;gap:8px;}
  .vr-toggle,.vr-seg label{cursor:pointer;position:relative;}
  .vr-toggle input,.vr-seg input{position:absolute;opacity:0;width:1px;height:1px;}
  .vr-toggle span{display:inline-flex;align-items:center;height:34px;padding:0 13px;border:1px solid var(--input);border-radius:var(--radius-sm);background:var(--background);font-size:13px;font-weight:500;box-shadow:var(--shadow-sm);transition:background-color .15s,border-color .15s,color .15s;}
  .vr-toggle:hover span{background:var(--muted);}
  .vr-toggle input:checked+span{background:var(--foreground);border-color:var(--foreground);color:#fff;}
  .vr-toggle input:focus-visible+span,.vr-seg input:focus-visible+span{box-shadow:0 0 0 3px var(--ring);}

  /* Segmented yes/no (tabs list) */
  .vr-seg{display:inline-flex;align-self:flex-start;gap:2px;padding:3px;border:1px solid transparent;border-radius:var(--radius-sm);background:var(--muted);}
  .vr-seg span{display:block;min-width:64px;padding:5px 16px;border-radius:6px;text-align:center;font-size:13.5px;font-weight:500;color:var(--muted-foreground);transition:background-color .15s,color .15s;}
  .vr-seg input:checked+span{background:var(--background);color:var(--foreground);box-shadow:var(--shadow-sm);}

  /* Documents */
  .vr-docs{display:grid;gap:12px;}
  .vr-doc{border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;display:grid;gap:14px;}
  .vr-doc-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;}
  .vr-doc-head b{font-size:14px;font-weight:600;}
  .vr-doc-head em{font-style:normal;font-size:12.5px;color:var(--muted-foreground);margin-left:8px;}
  .vr-doc-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:12px;}
  .vr-alert{display:flex;gap:10px;align-items:flex-start;margin-top:16px;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--muted);color:var(--muted-foreground);font-size:13px;}
  .vr-alert svg{flex:none;margin-top:2px;color:var(--foreground);}

  /* Checkbox */
  .vr-check{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;line-height:1.55;cursor:pointer;}
  .vr-check input{appearance:none;flex:none;width:16px;height:16px;margin-top:3px;border:1px solid #a1a1aa;border-radius:4px;background:var(--background);box-shadow:var(--shadow-sm);cursor:pointer;transition:background-color .15s,border-color .15s;}
  .vr-check input:checked{background:var(--primary) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center/12px no-repeat;border-color:var(--primary);}
  .vr-check input:focus-visible{outline:none;box-shadow:0 0 0 3px var(--ring);}
  .vr-check.is-invalid{color:var(--destructive);}
  .vr-check.is-invalid input{border-color:var(--destructive);}

  /* Actions */
  .vr-actions{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;}
  .vr-form-error{font-size:13.5px;font-weight:500;color:var(--destructive);}
  .vr-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 24px;border:1px solid transparent;border-radius:var(--radius-sm);background:var(--primary);color:#fff;font:inherit;font-size:14px;font-weight:600;box-shadow:var(--shadow-sm);cursor:pointer;transition:background-color .15s;}
  .vr-btn:hover{background:var(--primary-hover);}
  .vr-btn:focus-visible{outline:none;box-shadow:0 0 0 3px var(--ring);}
  .vr-btn[disabled]{opacity:.6;pointer-events:none;}
  .vr-btn-outline{background:var(--background);color:var(--foreground);border-color:var(--input);}
  .vr-btn-outline:hover{background:var(--muted);}

  /* Success */
  .vr-done{text-align:center;padding:48px 28px;}
  .vr-tick{width:64px;height:64px;border-radius:999px;background:var(--success-soft);border:1px solid var(--success-border);color:var(--success);display:grid;place-items:center;margin:0 auto 18px;}
  .vr-done h2{font-size:28px;font-weight:800;color:var(--navy);margin-bottom:6px;}
  .vr-done p{color:var(--muted-foreground);max-width:54ch;margin:0 auto;}
  .vr-ref{display:inline-block;margin:18px 0 10px;padding:9px 20px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--muted);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:18px;font-weight:600;letter-spacing:.04em;}
  .vr-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;text-align:left;margin:24px 0;}
  .vr-steps div{border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;font-size:13px;color:var(--muted-foreground);}
  .vr-steps b{display:block;color:var(--foreground);font-weight:600;margin-bottom:2px;}

  @media (max-width:900px){
    .vr-main{grid-template-columns:minmax(0,1fr);gap:20px;}
    .vr-index{position:static;}
    .vr-index ol{display:flex;overflow-x:auto;gap:6px;padding-bottom:4px;}
    .vr-index a{white-space:nowrap;border:1px solid var(--border);background:var(--card);}
    .vr-doc-grid{grid-template-columns:repeat(2,minmax(0,1fr));}
  }
  @media (max-width:600px){
    .vr-wrap{padding-inline:16px;}
    .vr-head .vr-wrap{padding-block:32px 28px;}
    .vr-grid,.vr-doc-grid,.vr-steps{grid-template-columns:minmax(0,1fr);}
    .vr-card-head{padding:18px 16px 0;}
    .vr-card-body{padding:16px;}
    .vr-actions .vr-btn{width:100%;}
  }
  @media (prefers-reduced-motion:reduce){.vr *{transition:none !important;}}
</style>
@endpush

@push('scripts')
<script>
(function () {
  const form = document.getElementById('vendorForm');
  const done = document.getElementById('vrDone');
  const formError = document.getElementById('vrFormError');
  const submitBtn = document.getElementById('vrSubmit');
  const MAX_FILE_BYTES = {{ $maxFileMb }} * 1024 * 1024;
  const SECTION_IDS = @json(array_keys($sections));
  const $ = (sel) => form.querySelector(sel);
  const byName = (name) => form.querySelector(`[name="${name}"]`);
  const checked = (name) => form.querySelector(`input[name="${name}"]:checked`);
  const vatYes = () => checked('vat_registered')?.value === 'Yes';
  const docInput = (type, key) => byName(`documents[${type}][${key}]`);

  const now = new Date();
  const today = new Date(now - now.getTimezoneOffset() * 6e4).toISOString().slice(0, 10);
  document.getElementById('vrSignDate').value = today;

  // ── Document status badges ─────────────────────────────────────────
  function docStatus(doc) {
    const type = doc.dataset.doc;
    const badge = doc.querySelector('[data-doc-status]');
    const hasFile = docInput(type, 'file').files.length > 0;
    const expiry = docInput(type, 'expiry_date').value;
    badge.className = 'vr-badge vr-badge-secondary';
    if (!hasFile) { badge.textContent = 'Not uploaded'; return; }
    if (!expiry) {
      if (doc.dataset.expires === '1') { badge.textContent = 'Pending expiry date'; badge.classList.add('is-warn'); }
      else { badge.textContent = 'Uploaded'; badge.classList.add('is-ok'); }
      return;
    }
    const days = Math.ceil((new Date(expiry + 'T00:00:00') - new Date(new Date().toDateString())) / 864e5);
    if (days < 0) { badge.textContent = 'Expired'; badge.classList.add('is-bad'); }
    else if (days <= 30) { badge.textContent = `Expires in ${days} day${days === 1 ? '' : 's'}`; badge.classList.add('is-warn'); }
    else { badge.textContent = 'Valid'; badge.classList.add('is-ok'); }
  }
  const docs = [...form.querySelectorAll('.vr-doc')];

  // ── Conditional fields ─────────────────────────────────────────────
  function syncConditionals() {
    document.getElementById('vrOtherWrap').hidden = checked('category')?.value !== 'Other';
    document.getElementById('vrTrnWrap').hidden = !vatYes();
  }

  $('#iban').addEventListener('input', (e) => {
    const v = e.target.value.replace(/\s+/g, '').toUpperCase();
    e.target.value = v.replace(/(.{4})/g, '$1 ').trim();
  });

  // ── Section progress ───────────────────────────────────────────────
  function sectionDone(id) {
    const sec = document.getElementById(id);
    const ok = [...sec.querySelectorAll('input,select,textarea')].every((el) => {
      if (el.closest('[hidden]') || !el.required) return true;
      if (el.type === 'checkbox') return el.checked;
      if (el.type === 'file') return el.files.length > 0;
      return el.value.trim() !== '';
    });
    if (id === 'services') return ok && !!checked('category') && (checked('category').value !== 'Other' || $('#category_other').value.trim() !== '');
    if (id === 'documents') return ok && (!vatYes() || docInput('vat_certificate', 'file').files.length > 0);
    if (id === 'commercial') return ok && !!checked('vat_registered') && (!vatYes() || $('#trn').value.trim() !== '');
    return ok;
  }
  function progress() {
    let n = 0;
    SECTION_IDS.forEach((id) => {
      const isDone = !!sectionDone(id);
      if (isDone) n++;
      document.querySelector(`.vr-index [data-s="${id}"]`).classList.toggle('done', isDone);
    });
    document.getElementById('vrProgressText').textContent = `${n} of ${SECTION_IDS.length}`;
    document.getElementById('vrProgressBar').style.width = (n / SECTION_IDS.length * 100) + '%';
  }

  // ── Errors ─────────────────────────────────────────────────────────
  let firstInvalid = null;
  function fail(el, msg) {
    if (!el) return;
    firstInvalid = firstInvalid || el;
    const check = el.closest('.vr-check');
    if (check) { check.classList.add('is-invalid'); return; }
    const field = el.closest('.vr-field');
    if (!field) return;
    if (field.dataset.group || el.type === 'radio') field.classList.add('vr-invalid');
    else el.classList.add('is-invalid');
    if (field.querySelector('.vr-error')) return;
    const m = document.createElement('span');
    m.className = 'vr-error';
    m.textContent = msg;
    field.appendChild(m);
  }
  function clearField(el) {
    el.classList.remove('is-invalid');
    el.closest('.vr-check')?.classList.remove('is-invalid');
    const field = el.closest('.vr-field');
    if (field) { field.classList.remove('vr-invalid'); field.querySelector('.vr-error')?.remove(); }
  }
  function clearAll() {
    firstInvalid = null;
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    form.querySelectorAll('.vr-invalid').forEach((el) => el.classList.remove('vr-invalid'));
    form.querySelectorAll('.vr-error').forEach((el) => el.remove());
    formError.textContent = '';
  }
  function focusFirstInvalid(message) {
    formError.textContent = message;
    if (!firstInvalid) return;
    firstInvalid.scrollIntoView({ block: 'center' });
    firstInvalid.focus({ preventScroll: true });
  }

  function validate() {
    clearAll();
    form.querySelectorAll('[required]').forEach((el) => {
      if (el.closest('[hidden]')) return;
      if (el.type === 'checkbox') { if (!el.checked) fail(el); return; }
      if (el.type === 'file' ? !el.files.length : !el.value.trim()) fail(el, 'This field is required.');
    });

    const email = $('#email');
    if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) fail(email, 'Enter a valid email, like name@company.ae.');
    const finance = $('#finance_email');
    if (finance.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(finance.value)) fail(finance, 'Enter a valid email address.');
    const phone = $('#phone');
    if (phone.value && phone.value.replace(/\D/g, '').length < 9) fail(phone, 'Enter a full phone number with country code.');

    const iban = $('#iban'), ibanValue = iban.value.replace(/\s/g, '');
    if (ibanValue && ibanValue.startsWith('AE') && !/^AE\d{21}$/.test(ibanValue)) fail(iban, 'UAE IBANs are AE followed by 21 digits (23 characters).');
    else if (ibanValue && !/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/.test(ibanValue)) fail(iban, 'Check the IBAN. It starts with a 2-letter country code.');
    const swift = $('#swift');
    if (swift.value && !/^[A-Za-z0-9]{8}([A-Za-z0-9]{3})?$/.test(swift.value.trim())) fail(swift, 'SWIFT / BIC codes are 8 or 11 characters.');

    if (!checked('category')) fail(byName('category'), 'Choose a category.');
    else if (checked('category').value === 'Other' && !$('#category_other').value.trim()) fail($('#category_other'), 'Describe the category.');
    if (!checked('vat_registered')) fail(byName('vat_registered'), 'Choose Yes or No.');
    if (vatYes()) {
      const trn = $('#trn');
      if (!/^\d{15}$/.test(trn.value.trim())) fail(trn, 'TRN must be 15 digits.');
      if (!docInput('vat_certificate', 'file').files.length) fail(docInput('vat_certificate', 'file'), 'Upload your VAT certificate.');
    }

    docs.forEach((doc) => {
      const type = doc.dataset.doc;
      const expiry = docInput(type, 'expiry_date'), issue = docInput(type, 'issue_date');
      if (doc.dataset.expires === '1' && docInput(type, 'file').files.length && !expiry.value) fail(expiry, 'Add the expiry date.');
      if (issue.value && expiry.value && expiry.value < issue.value) fail(expiry, 'Expiry must be after the issue date.');
    });
    form.querySelectorAll('input[type=file]').forEach((el) => {
      const file = el.files[0];
      if (!file) return;
      if (!/\.(pdf|jpe?g|png)$/i.test(file.name)) fail(el, 'Upload a PDF, JPG or PNG file.');
      else if (file.size > MAX_FILE_BYTES) fail(el, 'This file is larger than {{ $maxFileMb }} MB.');
    });

    const start = $('#contract_start').value, end = $('#contract_end').value;
    if (start && end && end < start) fail($('#contract_end'), 'End date must be after the start date.');

    return !firstInvalid;
  }

  // "documents.trade_licence.file" -> the input named documents[trade_licence][file]
  function fieldFor(key) {
    const [head, ...rest] = key.split('.');
    return byName(head + rest.map((p) => `[${p}]`).join(''));
  }

  // ── Submit ─────────────────────────────────────────────────────────
  async function send(token) {
    return fetch(@json(route('vendor-registration.store')), {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
      body: new FormData(form),
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validate()) { focusFirstInvalid('Some details need attention. Check the highlighted fields.'); return; }

    const label = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting…';

    try {
      let response = await send(document.querySelector('meta[name="csrf-token"]').content);
      // The page sat open past the session lifetime - get a fresh token and retry once.
      if (response.status === 419) {
        const fresh = await (await fetch(@json(route('csrf.token')), { headers: { 'Accept': 'application/json' } })).json();
        document.querySelector('meta[name="csrf-token"]').content = fresh.token;
        response = await send(fresh.token);
      }

      if (response.status === 413) { formError.textContent = 'Your files are too large to send together. Use smaller files and try again.'; return; }
      if (response.status === 429) { formError.textContent = 'Too many attempts. Please wait a minute and try again.'; return; }

      const data = await response.json().catch(() => ({}));

      if (response.status === 422 && data.errors) {
        Object.entries(data.errors).forEach(([key, messages]) => fail(fieldFor(key), messages[0]));
        focusFirstInvalid(firstInvalid ? 'Some details need attention. Check the highlighted fields.' : Object.values(data.errors).flat()[0]);
        return;
      }
      if (!response.ok || !data.success) { formError.textContent = 'Something went wrong. Please try again.'; return; }

      document.getElementById('vrRef').textContent = data.reference;
      document.getElementById('vrDoneMsg').textContent =
        `Thank you, ${$('#contact_name').value.trim().split(' ')[0]}. ${$('#legal_name').value.trim()} is now under review. We will email ${$('#email').value.trim()} within 5 working days.`;
      form.hidden = true;
      done.hidden = false;
      done.scrollIntoView({ block: 'start' });
    } catch (err) {
      formError.textContent = 'Network error. Check your connection and try again.';
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = label;
    }
  });

  form.addEventListener('input', (e) => { clearField(e.target); progress(); });
  form.addEventListener('change', (e) => {
    clearField(e.target);
    syncConditionals();
    const doc = e.target.closest('.vr-doc');
    if (doc) docStatus(doc);
    progress();
  });

  document.getElementById('vrAgain').addEventListener('click', () => {
    form.reset();
    clearAll();
    document.getElementById('vrSignDate').value = today;
    syncConditionals();
    docs.forEach(docStatus);
    done.hidden = true;
    form.hidden = false;
    progress();
    window.scrollTo(0, 0);
  });

  progress();
})();
</script>
@endpush

@extends('layouts.landing')

@section('title', 'Engage Clinic · Privacy Policy')

@php
  $activePage = 'privacy-policy';
@endphp

@section('content')

<!-- Hero -->
<section class="js-hero py-20 md:py-28 text-center" style="background:var(--navy-deep);">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <span class="eyebrow-plain" style="color:var(--pink-mid);">Legal</span>
    <h1 class="display mt-4 text-3xl sm:text-5xl font-extrabold leading-[1.15] text-white">Privacy Policy</h1>
    <p class="mt-5 text-white/75 text-base sm:text-lg leading-relaxed">Last updated: {{ now()->format('F j, Y') }}</p>
  </div>
</section>

<!-- Policy body -->
<section class="py-16 md:py-24 dotted">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <div class="card p-8 lg:p-10 space-y-10">

      <div>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          Engage Clinic ("we", "us", "our") provides ABA, speech, and related therapy services for children in Abu Dhabi, UAE.
          This policy explains what personal information we collect, how we use it, and the choices you have — including
          when you contact us through our website, phone, WhatsApp, or Instagram.
        </p>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">Information we collect</h2>
        <ul class="list-disc pl-5 space-y-2 text-[var(--text-secondary)] leading-relaxed">
          <li><strong>Contact &amp; booking details</strong> — parent/guardian name, phone number, email, and information about your child (name, age, condition of interest) submitted through our website forms or consultation bookings.</li>
          <li><strong>Insurance information</strong> — insurance provider, if shared, to help us verify coverage (e.g. Daman, Thiqa).</li>
          <li><strong>Messages sent via WhatsApp or Instagram</strong> — if you message us on WhatsApp Business or Instagram Direct, we receive your message content, phone number or Instagram account identifier, and profile name, in order to respond to you and, where relevant, create a record of your enquiry.</li>
          <li><strong>Appointment &amp; therapy records</strong> — scheduling details and session notes for patients enrolled in our programs.</li>
          <li><strong>Website usage data</strong> — standard technical data (e.g. browser, general location) collected automatically when you visit our site.</li>
        </ul>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">How we use your information</h2>
        <ul class="list-disc pl-5 space-y-2 text-[var(--text-secondary)] leading-relaxed">
          <li>To respond to enquiries and book consultations or therapy sessions.</li>
          <li>To communicate with you about appointments, therapy progress, and billing/insurance.</li>
          <li>To reply to messages you send us via WhatsApp or Instagram, using Meta's WhatsApp Business Platform and Instagram Messaging API.</li>
          <li>To maintain accurate patient and family records for the care we provide.</li>
          <li>To improve our services and website.</li>
        </ul>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">Children's information</h2>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          Some information we hold relates to children under our care. This information is always provided to us by
          a parent or legal guardian, and is used solely to deliver and coordinate therapy services for that child.
          We do not knowingly collect information directly from children without parental involvement.
        </p>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">How we share information</h2>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          We do not sell your personal information. We share information only with:
        </p>
        <ul class="list-disc pl-5 space-y-2 text-[var(--text-secondary)] leading-relaxed mt-2">
          <li><strong>Meta Platforms, Inc.</strong> — to deliver and receive WhatsApp and Instagram messages through their respective APIs.</li>
          <li><strong>Service providers</strong> who help us operate our systems (e.g. hosting), under confidentiality obligations.</li>
          <li>Insurance providers, only with your consent, to process claims or verify coverage.</li>
          <li>Authorities, where required by UAE law.</li>
        </ul>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">Data retention</h2>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          We retain enquiry and messaging records for as long as reasonably necessary to respond to you or provide
          ongoing care, and patient records for as long as required by applicable healthcare recordkeeping
          obligations in the UAE.
        </p>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">Your rights</h2>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          You may ask us to access, correct, or delete the personal information we hold about you or your child by
          contacting us using the details below. We will respond within a reasonable timeframe.
        </p>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">Security</h2>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          We take reasonable technical and organisational measures to protect the personal information we hold
          against unauthorised access, loss, or misuse.
        </p>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">Changes to this policy</h2>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          We may update this policy from time to time. Material changes will be reflected by updating the "Last
          updated" date above.
        </p>
      </div>

      <div>
        <h2 class="display text-xl font-extrabold text-[var(--navy)] mb-3">Contact us</h2>
        <p class="text-[var(--text-secondary)] leading-relaxed">
          If you have questions about this policy or how we handle your information, contact us at:
        </p>
        <ul class="list-none space-y-1 text-[var(--text-secondary)] leading-relaxed mt-2">
          <li>Email: <a href="mailto:info@engagebehavior.com" class="font-bold text-[var(--navy)]">info@engagebehavior.com</a></li>
          <li>Phone: <a href="tel:+971508846801" class="font-bold text-[var(--navy)]">+971 50 884 6801</a></li>
          <li>Address: Office no. 1203, ADCP Commercial Tower-C, Electra Street, Abu Dhabi, UAE</li>
        </ul>
      </div>

    </div>
  </div>
</section>

@endsection

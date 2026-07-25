<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Engage Clinic · Therapy</title>
  <!-- Google Font & Tailwind via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .hero-bg {
      background: linear-gradient(135deg, #f6f3ee 0%, #ede8e0 100%);
    }
    .card-hover {
      transition: all 0.2s ease-in-out;
    }
    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
    }
    .btn-primary {
      background-color: #2563eb;
      transition: background-color 0.15s;
    }
    .btn-primary:hover {
      background-color: #1d4ed8;
    }
    .btn-outline {
      border: 1px solid #2563eb;
      color: #2563eb;
      transition: all 0.15s;
    }
    .btn-outline:hover {
      background-color: #2563eb;
      color: white;
    }
    .service-icon {
      background-color: #dbeafe;
      color: #2563eb;
    }
    .footer-bg {
      background-color: #0b1121;
    }
    .testimonial-card {
      background: white;
      border-radius: 1.5rem;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -2px rgba(0,0,0,0.02);
    }
    .section-title {
      font-weight: 600;
      letter-spacing: -0.02em;
    }
    .text-balance {
      text-wrap: balance;
    }
    .nav-book-btn {
      background-color: #2563eb;
      color: white;
      padding: 0.5rem 1.25rem;
      border-radius: 9999px;
      font-size: 0.875rem;
      font-weight: 600;
      transition: all 0.15s;
      box-shadow: 0 4px 8px -2px rgba(37,99,235,0.25);
    }
    .nav-book-btn:hover {
      background-color: #1d4ed8;
      transform: translateY(-1px);
    }
    .offer-card {
      background: white;
      border-radius: 1.5rem;
      padding: 1.75rem;
      border: 1px solid #f0ebe5;
      transition: all 0.3s ease;
    }
    .offer-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px -12px rgba(0,0,0,0.08);
      border-color: #2563eb;
    }
    .offer-icon {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      margin-bottom: 1rem;
    }
    .offer-icon.blue { background: #dbeafe; color: #2563eb; }
    .offer-icon.pink { background: #fce7f3; color: #db2777; }
    .offer-icon.green { background: #d1fae5; color: #059669; }
    .offer-icon.purple { background: #ede9fe; color: #7c3aed; }
    .offer-icon.orange { background: #fef3c7; color: #d97706; }
    .offer-icon.teal { background: #ccfbf1; color: #0d9488; }
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      color: #1F2937;
      line-height: 1.2;
    }
    .stat-label {
      font-size: 0.875rem;
      color: #6b7280;
      font-weight: 500;
    }
    .about-bg {
      background: linear-gradient(135deg, #f8f6f4 0%, #f0ebe5 100%);
    }
    .nav-logo {
      height: 40px;
      width: auto;
      display: block;
    }
  </style>
</head>
<body class="bg-[#F6F3EE] text-[#1F2937] antialiased">

  <!-- Navigation -->
  <nav class="bg-white/80 backdrop-blur-sm border-b border-gray-100/80 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between h-16">
      <div class="flex items-center gap-2">
        <a href="/" class="flex items-center">
          <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic" class="nav-logo">
        </a>
      
      </div>
      <div class="hidden md:flex items-center gap-7 text-sm font-medium text-gray-700">
        <a href="#home" class="hover:text-[#2563EB] transition">Home</a>
        <a href="#about" class="hover:text-[#2563EB] transition">About</a>
        <a href="#services" class="hover:text-[#2563EB] transition">Services</a>
        <a href="#therapists" class="hover:text-[#2563EB] transition">Therapists</a>
        <a href="#testimonials" class="hover:text-[#2563EB] transition">Testimonials</a>
        <a href="#contact" class="hover:text-[#2563EB] transition">Contact</a>
      </div>
      <div class="flex items-center gap-3">
        <a href="#appointment" class="hidden sm:inline-block nav-book-btn">Book Session</a>
        <button id="mobile-menu-btn" class="md:hidden p-2 rounded-full hover:bg-gray-100 transition">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
      </div>
    </div>
    <div id="mobile-menu" class="md:hidden bg-white border-b border-gray-100 px-6 pb-4 hidden flex-col gap-3 text-sm font-medium text-gray-700">
      <a href="#home" class="hover:text-[#2563EB]">Home</a>
      <a href="#about" class="hover:text-[#2563EB]">About</a>
      <a href="#services" class="hover:text-[#2563EB]">Services</a>
      <a href="#therapists" class="hover:text-[#2563EB]">Therapists</a>
      <a href="#testimonials" class="hover:text-[#2563EB]">Testimonials</a>
      <a href="#contact" class="hover:text-[#2563EB]">Contact</a>
      <a href="#appointment" class="mt-2 bg-[#2563EB] text-white px-4 py-2 rounded-full text-center">Book Session</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <section id="home" class="hero-bg pt-10 pb-16 md:pt-16 md:pb-24">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
      <div>
        <span class="inline-block bg-[#dbeafe] text-[#2563EB] text-xs font-semibold px-4 py-1.5 rounded-full mb-4">🧘‍♀️ Your well-being, our focus</span>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-[1.15] text-balance">
          Compassionate therapy <br><span class="text-[#2563EB]">for every mind</span>
        </h1>
        <p class="text-gray-600 text-lg mt-4 max-w-lg leading-relaxed">
          We combine evidence-based approaches with a warm, supportive environment to help you thrive.
        </p>
        <div class="flex flex-wrap items-center gap-4 mt-8">
          <a href="#appointment" class="btn-primary text-white px-8 py-3.5 rounded-full text-sm font-semibold shadow-lg shadow-blue-200/60">Book a Session</a>
          <a href="#about" class="btn-outline px-8 py-3.5 rounded-full text-sm font-semibold">Learn More</a>
        </div>
        <div class="flex items-center gap-6 mt-8 text-sm text-gray-500">
          <span class="flex items-center gap-1"><span class="text-[#10B981] text-lg">✓</span> Personalized care</span>
          <span class="flex items-center gap-1"><span class="text-[#10B981] text-lg">✓</span> Safe & confidential</span>
        </div>
      </div>
      <div class="relative flex justify-center">
        <div class="w-full max-w-md aspect-[4/3] bg-gradient-to-br from-[#dbeafe] to-[#b3d4f5] rounded-3xl shadow-2xl flex items-center justify-center text-[#2563EB] text-7xl font-light relative overflow-hidden">
          <span class="absolute inset-0 flex items-center justify-center text-8xl opacity-30">🧠</span>
          <span class="relative z-10 text-9xl">🌸</span>
          <div class="absolute bottom-4 right-4 bg-white/50 backdrop-blur-sm px-4 py-1.5 rounded-full text-xs font-semibold text-[#1F2937] shadow-sm">+200 clients</div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Us / What We Offer -->
  <section id="about" class="about-bg py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-16">
        <span class="inline-block bg-[#dbeafe] text-[#2563EB] text-xs font-semibold px-4 py-1.5 rounded-full mb-4">About Us</span>
        <h2 class="section-title text-3xl md:text-4xl mb-4">What <span class="text-[#2563EB]">We Offer</span></h2>
        <p class="text-gray-500 max-w-2xl mx-auto">Comprehensive therapeutic services designed to support individuals and families on their journey to well-being.</p>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-16">
        <div class="text-center">
          <div class="stat-number">200+</div>
          <div class="stat-label">Happy Families</div>
        </div>
        <div class="text-center">
          <div class="stat-number">5</div>
          <div class="stat-label">Expert Therapists</div>
        </div>
        <div class="text-center">
          <div class="stat-number">95%</div>
          <div class="stat-label">Satisfaction Rate</div>
        </div>
        <div class="text-center">
          <div class="stat-number">8</div>
          <div class="stat-label">Service Types</div>
        </div>
      </div>

      <!-- Offer Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="offer-card">
          <div class="offer-icon blue">🧑‍⚕️</div>
          <h4 class="font-bold text-lg text-[#1F2937] mb-1">Individual Therapy</h4>
          <p class="text-gray-500 text-sm leading-relaxed">One-on-one sessions tailored to your unique needs. Evidence-based approaches including CBT, DBT, and mindfulness.</p>
        </div>
        <div class="offer-card">
          <div class="offer-icon pink">👨‍👩‍👧‍👦</div>
          <h4 class="font-bold text-lg text-[#1F2937] mb-1">Family Therapy</h4>
          <p class="text-gray-500 text-sm leading-relaxed">Strengthen family connections and improve communication. Build healthier relationships with guided support.</p>
        </div>
        <div class="offer-card">
          <div class="offer-icon green">💑</div>
          <h4 class="font-bold text-lg text-[#1F2937] mb-1">Couples Counseling</h4>
          <p class="text-gray-500 text-sm leading-relaxed">Build healthier relationships with evidence-based approaches. Improve communication and deepen connection.</p>
        </div>
        <div class="offer-card">
          <div class="offer-icon purple">🧘</div>
          <h4 class="font-bold text-lg text-[#1F2937] mb-1">ABA Therapy</h4>
          <p class="text-gray-500 text-sm leading-relaxed">Applied Behavior Analysis for children with autism. 1:1 programs from 10 to 30 hours per week, supervised by BCBAs.</p>
        </div>
        <div class="offer-card">
          <div class="offer-icon orange">🌱</div>
          <h4 class="font-bold text-lg text-[#1F2937] mb-1">Speech & OT</h4>
          <p class="text-gray-500 text-sm leading-relaxed">Speech-language pathology and occupational therapy services. Supporting communication, sensory, and motor skills.</p>
        </div>
        <div class="offer-card">
          <div class="offer-icon teal">📋</div>
          <h4 class="font-bold text-lg text-[#1F2937] mb-1">Diagnostic Assessment</h4>
          <p class="text-gray-500 text-sm leading-relaxed">Comprehensive psychological and developmental assessments. ADOS-2, cognitive, and behavioral evaluations.</p>
        </div>
      </div>

      <!-- Why Choose Us -->
      <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 bg-white/60 rounded-2xl p-8 border border-gray-200/50">
        <div class="flex items-start gap-4">
          <span class="text-2xl">🛡️</span>
          <div>
            <h5 class="font-semibold text-[#1F2937]">Licensed & Certified</h5>
            <p class="text-sm text-gray-500">All therapists are licensed and certified in their respective fields.</p>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <span class="text-2xl">💳</span>
          <div>
            <h5 class="font-semibold text-[#1F2937]">Insurance Accepted</h5>
            <p class="text-sm text-gray-500">We work with Daman, Thiqa, ADNIC, AXA, and other major insurers.</p>
          </div>
        </div>
        <div class="flex items-start gap-4">
          <span class="text-2xl">📍</span>
          <div>
            <h5 class="font-semibold text-[#1F2937]">Khalifa City, Abu Dhabi</h5>
            <p class="text-sm text-gray-500">Conveniently located clinic with flexible appointment scheduling.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Choose Us (Original) -->
  <section class="py-16 bg-white" id="why-choose">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-14">
        <h2 class="section-title text-3xl md:text-4xl">Why <span class="text-[#2563EB]">Engage Clinic</span></h2>
        <p class="text-gray-500 max-w-2xl mx-auto mt-2">We are dedicated to providing exceptional therapeutic care in a comfortable space.</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-[#F8FAFC] p-6 rounded-2xl card-hover border border-gray-100/80">
          <div class="w-12 h-12 rounded-full service-icon flex items-center justify-center text-2xl">🧠</div>
          <h4 class="font-semibold text-lg mt-4">Experienced Therapists</h4>
          <p class="text-gray-500 text-sm leading-relaxed mt-1">Professional team committed to compassionate, evidence-based care.</p>
        </div>
        <div class="bg-[#F8FAFC] p-6 rounded-2xl card-hover border border-gray-100/80">
          <div class="w-12 h-12 rounded-full service-icon flex items-center justify-center text-2xl">🌿</div>
          <h4 class="font-semibold text-lg mt-4">Holistic Approach</h4>
          <p class="text-gray-500 text-sm leading-relaxed mt-1">Mind-body integration for lasting well-being and resilience.</p>
        </div>
        <div class="bg-[#F8FAFC] p-6 rounded-2xl card-hover border border-gray-100/80">
          <div class="w-12 h-12 rounded-full service-icon flex items-center justify-center text-2xl">🤝</div>
          <h4 class="font-semibold text-lg mt-4">Affordable & Accessible</h4>
          <p class="text-gray-500 text-sm leading-relaxed mt-1">Quality therapy at reasonable rates, with flexible scheduling.</p>
        </div>
        <div class="bg-[#F8FAFC] p-6 rounded-2xl card-hover border border-gray-100/80">
          <div class="w-12 h-12 rounded-full service-icon flex items-center justify-center text-2xl">🕊️</div>
          <h4 class="font-semibold text-lg mt-4">Safe Environment</h4>
          <p class="text-gray-500 text-sm leading-relaxed mt-1">Relaxed, welcoming clinic designed for your comfort.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Services -->
  <section id="services" class="py-16 bg-[#F8FAFC]">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="section-title text-3xl md:text-4xl">Our <span class="text-[#2563EB]">Therapy Services</span></h2>
        <p class="text-gray-500 mt-2">Comprehensive support for your mental health journey</p>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">🧑‍⚕️</span><h5 class="font-semibold text-sm">Individual Therapy</h5><p class="text-xs text-gray-400 mt-1">One-on-one sessions</p></div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">👨‍👩‍👧‍👦</span><h5 class="font-semibold text-sm">Family Therapy</h5><p class="text-xs text-gray-400 mt-1">Strengthen connections</p></div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">💑</span><h5 class="font-semibold text-sm">Couples Counseling</h5><p class="text-xs text-gray-400 mt-1">Build healthier relationships</p></div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">🧘</span><h5 class="font-semibold text-sm">Mindfulness & Stress</h5><p class="text-xs text-gray-400 mt-1">Reduce anxiety, find calm</p></div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">🌱</span><h5 class="font-semibold text-sm">Trauma Therapy</h5><p class="text-xs text-gray-400 mt-1">Healing & recovery</p></div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">🧒</span><h5 class="font-semibold text-sm">Child & Adolescent</h5><p class="text-xs text-gray-400 mt-1">Support for young minds</p></div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">📋</span><h5 class="font-semibold text-sm">Psychological Assessment</h5><p class="text-xs text-gray-400 mt-1">Comprehensive evaluation</p></div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center card-hover"><span class="text-3xl block mb-2">💬</span><h5 class="font-semibold text-sm">Group Therapy</h5><p class="text-xs text-gray-400 mt-1">Shared growth & support</p></div>
      </div>
    </div>
  </section>

  <!-- Therapists -->
  <section id="therapists" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="section-title text-3xl md:text-4xl">Meet our <span class="text-[#2563EB]">Therapists</span></h2>
        <p class="text-gray-500 mt-2">Compassionate professionals dedicated to your growth</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
        <div class="bg-[#F8FAFC] p-6 rounded-2xl card-hover border border-gray-100 text-center">
          <div class="w-24 h-24 rounded-full bg-[#dbeafe] mx-auto flex items-center justify-center text-4xl">👩‍⚕️</div>
          <h4 class="font-bold text-xl mt-4">Dr. Fatima Zahra</h4>
          <p class="text-[#2563EB] text-sm font-medium">Clinical Psychologist</p>
          <p class="text-gray-500 text-sm mt-2">12+ years in trauma & anxiety, CBT/DBT certified.</p>
        </div>
        <div class="bg-[#F8FAFC] p-6 rounded-2xl card-hover border border-gray-100 text-center">
          <div class="w-24 h-24 rounded-full bg-[#dbeafe] mx-auto flex items-center justify-center text-4xl">👨‍⚕️</div>
          <h4 class="font-bold text-xl mt-4">Mr. James Okafor</h4>
          <p class="text-[#2563EB] text-sm font-medium">Licensed Therapist</p>
          <p class="text-gray-500 text-sm mt-2">Specializes in family therapy & mindfulness-based approaches.</p>
        </div>
        <div class="bg-[#F8FAFC] p-6 rounded-2xl card-hover border border-gray-100 text-center">
          <div class="w-24 h-24 rounded-full bg-[#dbeafe] mx-auto flex items-center justify-center text-4xl">🧠</div>
          <h4 class="font-bold text-xl mt-4">Ms. Hanan Youssef</h4>
          <p class="text-[#2563EB] text-sm font-medium">ABA Therapist (RBT)</p>
          <p class="text-gray-500 text-sm mt-2">Specializes in ABA therapy & early intervention for children.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials -->
  <section id="testimonials" class="py-16 bg-[#F8FAFC]">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="section-title text-3xl md:text-4xl">Client <span class="text-[#2563EB]">Testimonials</span></h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        <div class="testimonial-card p-6">
          <div class="flex text-[#f59e0b] text-sm mb-2">⭐⭐⭐⭐⭐</div>
          <p class="text-gray-700 text-sm italic">"Engage Clinic gave me the tools to manage my anxiety. I feel like myself again."</p>
          <p class="font-medium mt-3 text-sm">— Noora (Umm Rashid)</p>
        </div>
        <div class="testimonial-card p-6">
          <div class="flex text-[#f59e0b] text-sm mb-2">⭐⭐⭐⭐⭐</div>
          <p class="text-gray-700 text-sm italic">"Warm, professional, and truly life-changing. Highly recommend their therapy."</p>
          <p class="font-medium mt-3 text-sm">— Fatima Al Shamsi</p>
        </div>
        <div class="testimonial-card p-6">
          <div class="flex text-[#f59e0b] text-sm mb-2">⭐⭐⭐⭐⭐</div>
          <p class="text-gray-700 text-sm italic">"Our son has made incredible progress since starting ABA therapy. The team is amazing!"</p>
          <p class="font-medium mt-3 text-sm">— Reem Al Falasi</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Appointment + Contact -->
  <section id="appointment" class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12">
      <div>
        <h2 class="section-title text-3xl">Book a <span class="text-[#2563EB]">Session</span></h2>
        <p class="text-gray-500 text-sm mt-1 mb-6">Fill in the details and we'll confirm your appointment.</p>
        <form class="space-y-4">
          <div><input type="text" placeholder="Full Name" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30"></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <input type="email" placeholder="Email" class="bg-[#F8FAFC] border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
            <input type="tel" placeholder="Phone" class="bg-[#F8FAFC] border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <input type="date" class="bg-[#F8FAFC] border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
            <input type="time" class="bg-[#F8FAFC] border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
          </div>
          <select class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30">
            <option>Individual Therapy</option>
            <option>Family Therapy</option>
            <option>Couples Counseling</option>
            <option>ABA Therapy</option>
            <option>Speech & OT</option>
            <option>Trauma Therapy</option>
            <option>Child & Adolescent</option>
            <option>Diagnostic Assessment</option>
          </select>
          <textarea rows="3" placeholder="Message (optional)" class="w-full bg-[#F8FAFC] border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB]/30"></textarea>
          <button type="submit" class="btn-primary w-full text-white py-3.5 rounded-xl text-sm font-semibold shadow-md shadow-blue-200/50">Book Now</button>
        </form>
      </div>
      <div id="contact" class="space-y-6">
        <h2 class="section-title text-3xl">Get in <span class="text-[#2563EB]">Touch</span></h2>
        <div class="space-y-3 text-sm text-gray-600">
          <p><span class="font-semibold text-gray-700">📍</span> Khalifa City, Abu Dhabi · Engage Clinic</p>
          <p><span class="font-semibold text-gray-700">📞</span> (971) 50 123 4567</p>
          <p><span class="font-semibold text-gray-700">✉️</span> hello@engageclinic.ae</p>
          <p><span class="font-semibold text-gray-700">🕒</span> Mon–Fri: 8:00am – 8:00pm · Sat: 9:00am – 3:00pm</p>
        </div>
        <div class="bg-[#e6f0fa] rounded-2xl p-4 h-48 flex items-center justify-center text-[#2563EB] font-medium border border-gray-200/60">
          <span class="flex items-center gap-2"><span class="text-2xl">🗺️</span> Find us on Google Maps</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer-bg text-gray-300 py-12">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
      <div>
        <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic" style="height: 35px; width: auto; margin-bottom: 8px;">
        <p class="text-xs text-gray-400">Therapy · Growth · Well-being</p>
      </div>
      <div><h6 class="text-white font-semibold mb-3">Quick Links</h6><ul class="text-sm space-y-1"><li><a href="#home" class="hover:text-white">Home</a></li><li><a href="#about" class="hover:text-white">About</a></li><li><a href="#services" class="hover:text-white">Services</a></li><li><a href="#therapists" class="hover:text-white">Therapists</a></li></ul></div>
      <div><h6 class="text-white font-semibold mb-3">Services</h6><ul class="text-sm space-y-1"><li>Individual Therapy</li><li>Family Therapy</li><li>ABA Therapy</li><li>Speech & OT</li><li>Diagnostic Assessment</li></ul></div>
      <div><h6 class="text-white font-semibold mb-3">Contact</h6><p class="text-sm">hello@engageclinic.ae</p><p class="text-sm">(971) 50 123 4567</p><div class="flex gap-3 mt-2 text-xl">🧠🌿💬</div></div>
    </div>
    <div class="max-w-7xl mx-auto px-6 lg:px-8 border-t border-gray-800/60 mt-8 pt-6 text-xs text-gray-500 flex flex-col md:flex-row justify-between">
      <span>© 2026 Engage Clinic. All rights reserved.</span>
      <span class="mt-2 md:mt-0">Made with 💙 for mental wellness</span>
    </div>
  </footer>

  <script>
    const menuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (menuBtn && mobileMenu) {
      menuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
      });
    }
    document.querySelectorAll('#mobile-menu a').forEach(link => {
      link.addEventListener('click', () => mobileMenu.classList.add('hidden'));
    });
  </script>
</body>
</html>
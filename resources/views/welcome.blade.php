<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Engage Clinic · Therapy, reimagined</title>
<link rel="icon" type="image/png" href="/uploads/engage.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#FFFFFF;
    --bg-secondary:#FAFAFA;
    --card:#FFFFFF;
    --border:#E4E4E7;
    --text:#0A0A0A;
    --text-secondary:#71717A;
    --accent:#00B8CC;
    --accent-bright:#00E5FF;
    --accent-hover:#00A3B5;
    --accent-soft:#ECFEFF;
    --radius:14px;
  }
  *{font-family:'Inter',system-ui,-apple-system,sans-serif;}
  body{background:var(--bg);color:var(--text);}

  .eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    background:var(--accent-soft);color:var(--accent-hover);
    border:1px solid #B7F2FA;
    font-size:12px;font-weight:600;letter-spacing:.02em;
    padding:6px 14px;border-radius:999px;
  }
  .dot{width:6px;height:6px;border-radius:999px;background:var(--accent);}

  .btn-primary{
    background:var(--accent);color:#fff;
    border-radius:10px;font-weight:600;font-size:14px;
    padding:12px 24px;
    position:relative;overflow:hidden;
  }

  .btn-outline{
    border:1px solid var(--border);color:var(--text);
    border-radius:10px;font-weight:600;font-size:14px;
    padding:12px 24px;background:#fff;
  }

  .card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--radius);
  }

  .icon-box{
    width:44px;height:44px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    background:var(--accent-soft);color:var(--accent-hover);
    font-size:20px;border:1px solid #D7F6FB;
  }

  .glass{
    background:rgba(255,255,255,.7);
    backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
    border:1px solid rgba(228,228,231,.8);
  }

  .glow{
    position:absolute;border-radius:999px;filter:blur(90px);
    background:radial-gradient(circle,rgba(0,229,255,.35),rgba(0,229,255,0) 70%);
    pointer-events:none;
  }

  .muted-photo{
    filter:grayscale(35%) contrast(1.02) brightness(1.02);
  }
  .photo-wrap{position:relative;overflow:hidden;}
  .photo-wrap::after{
    content:'';position:absolute;inset:0;
    background:linear-gradient(160deg, rgba(0,184,204,.12), rgba(0,0,0,0) 55%);
  }

  .nav-link{color:var(--text-secondary);font-size:14px;font-weight:500;}

  .stat-number{font-size:1.75rem;font-weight:800;letter-spacing:-.02em;color:var(--text);}
  @media (min-width:640px){ .stat-number{font-size:2.25rem;} }
  .stat-label{font-size:13px;color:var(--text-secondary);font-weight:500;}

  summary{cursor:pointer;list-style:none;}
  summary::-webkit-details-marker{display:none;}

  ::selection{background:var(--accent-bright);color:#00272B;}

  @media (max-width: 767.98px){
    #desktop-nav-links, #desktop-book-btn { display: none !important; }
    #mobile-menu-btn { display: inline-flex !important; }
  }
  @media (min-width: 768px){
    #mobile-menu-btn, #mobile-menu { display: none !important; }
  }
  #mobile-menu a{border-bottom:1px solid var(--border);}
  #mobile-menu a:last-of-type{border-bottom:none;}

  /* CRM-style form */
  .crm-input{
    width:100%;
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:10px;
    padding:10px 14px;
    font-size:13px;
    color:var(--text);
    transition:all .15s;
    outline:none;
  }
  .crm-input:focus{
    border-color:var(--accent);
    box-shadow:0 0 0 3px rgba(0,184,204,.1);
  }
  .crm-select{
    width:100%;
    background:var(--bg-secondary);
    border:1px solid var(--border);
    border-radius:10px;
    padding:10px 14px;
    font-size:13px;
    color:var(--text);
    outline:none;
    transition:all .15s;
  }
  .crm-select:focus{
    border-color:var(--accent);
    box-shadow:0 0 0 3px rgba(0,184,204,.1);
  }
  .crm-label{
    font-size:11px;
    font-weight:700;
    color:var(--text-secondary);
    text-transform:uppercase;
    letter-spacing:.04em;
    margin-bottom:4px;
    display:block;
  }

  /* Lead form specific */
  .lead-form-title{
    display:flex;align-items:center;gap:12px;
  }
  .lead-form-title h3{
    font-size:20px;font-weight:600;color:#16436E;
  }
  .lead-form-title p{
    font-size:12px;color:#98897A;font-weight:600;
  }
  .lead-form-close{
    width:32px;height:32px;border-radius:9px;
    border:1px solid #E2DACE;background:#fff;
    color:#5A6B7E;font-size:15px;font-weight:800;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
  }
  .lead-form-close:hover{background:#f5f5f5;}
  .btn-save{
    flex:1;background:#C8355F;color:#fff;
    border:none;border-radius:10px;padding:12px 0;
    font-size:13.5px;font-weight:800;cursor:pointer;
  }
  .btn-save:hover{background:#A82348;}
  .btn-cancel{
    width:120px;background:#fff;color:#5A6B7E;
    border:1px solid #E2DACE;border-radius:10px;padding:12px 0;
    font-size:13.5px;font-weight:800;cursor:pointer;
  }
  .btn-cancel:hover{background:#f5f5f5;}
  
  /* Map container */
  .map-container {
    position: relative;
    overflow: hidden;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    background: #f0f0f0;
    min-height: 250px;
  }
  .map-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
  }
</style>
</head>
<body class="antialiased">

<!-- Navigation -->
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div id="navInner" class="flex items-center justify-between h-14 sm:h-16 mt-2 sm:mt-3 rounded-2xl px-3 sm:px-4">
      <a href="/" class="flex items-center gap-2 font-bold text-lg tracking-tight">
        <img src="/uploads/engage.png" alt="Engage Clinic logo" class="h-14 w-auto object-contain">
      </a>
      <div id="desktop-nav-links" class="hidden md:flex items-center gap-8">
        <a href="#services" class="nav-link">Services</a>
        <a href="#showcase" class="nav-link">How it works</a>
        <a href="#team" class="nav-link">Therapists</a>
        <a href="#testimonials" class="nav-link">Stories</a>
        <a href="#faq" class="nav-link">FAQ</a>
      </div>
      <div class="flex items-center gap-3">
        <a href="#appointment" id="desktop-book-btn" class="btn-primary hidden md:inline-block">Book a session</a>
        <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg border border-[var(--border)]">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
      </div>
    </div>
    <div id="mobile-menu" class="md:hidden hidden flex flex-col bg-white border border-[var(--border)] rounded-2xl mt-2 p-2 shadow-xl">
      <a href="#services" class="nav-link px-3 py-3">Services</a>
      <a href="#showcase" class="nav-link px-3 py-3">How it works</a>
      <a href="#team" class="nav-link px-3 py-3">Therapists</a>
      <a href="#testimonials" class="nav-link px-3 py-3">Stories</a>
      <a href="#faq" class="nav-link px-3 py-3">FAQ</a>
      <a href="#appointment" class="btn-primary text-center mt-3">Book a session</a>
    </div>
  </div>
</nav>

<!-- Hero -->
<section id="home" class="relative pt-28 sm:pt-32 md:pt-40 pb-16 md:pb-24 overflow-hidden">
  <div class="glow w-[320px] h-[320px] sm:w-[420px] sm:h-[420px] md:w-[560px] md:h-[560px] -top-40 left-1/2 -translate-x-1/2 opacity-60"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-8 relative">
    <div class="max-w-3xl mx-auto text-center">
      <span class="eyebrow"><span class="dot"></span> Now booking in Khalifa City, Abu Dhabi</span>
      <h1 class="mt-6 text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.1] sm:leading-[1.05]">
        Therapy that fits<br>
        <span class="text-[var(--accent)]">your actual life</span>
      </h1>
      <p class="mt-6 text-lg text-[var(--text-secondary)] max-w-xl mx-auto leading-relaxed">
        Licensed therapists, evidence-based care, and flexible scheduling — individual, family, and child therapy under one roof.
      </p>
      <div class="flex flex-wrap items-center justify-center gap-4 mt-9">
        <a href="#appointment" class="btn-primary text-base px-7 py-3.5">Book a session →</a>
        <a href="#showcase" class="btn-outline text-base px-7 py-3.5">See how it works</a>
      </div>
      <div class="flex items-center justify-center gap-6 mt-8 text-sm text-[var(--text-secondary)]">
        <span class="flex items-center gap-1.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Insurance accepted</span>
        <span class="flex items-center gap-1.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> 100% confidential</span>
      </div>
    </div>

    <div class="relative mt-16 max-w-4xl mx-auto">
      <div class="card p-3 md:p-4 rounded-2xl relative">
        <div class="photo-wrap rounded-xl overflow-hidden aspect-[16/8]">
          <img src="https://images.unsplash.com/photo-1758273240631-59d44c8f5b66?fm=jpg&q=80&w=1600&auto=format&fit=crop" alt="Therapist listening attentively" class="w-full h-full object-cover muted-photo">
        </div>
        <div class="glass hidden lg:flex absolute -left-8 top-10 rounded-2xl px-5 py-4 items-center gap-3 shadow-xl">
          <div class="icon-box">✓</div>
          <div>
            <p class="text-sm font-semibold leading-none">Session confirmed</p>
            <p class="text-xs text-[var(--text-secondary)] mt-1">Tue, 4:00 PM · Dr. Zahra</p>
          </div>
        </div>
        <div class="glass hidden lg:block absolute -right-6 bottom-10 rounded-2xl px-5 py-4 shadow-xl">
          <p class="text-xs text-[var(--text-secondary)] mb-1">Satisfaction rate</p>
          <div class="flex items-end gap-2">
            <span class="text-2xl font-extrabold">95%</span>
            <span class="text-xs text-[var(--accent-hover)] font-semibold mb-1">↑ from 200+ clients</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Trusted by -->
<section class="py-10 md:py-14 border-y border-[var(--border)] bg-[var(--bg-secondary)]">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <p class="text-center text-xs font-semibold tracking-widest uppercase text-[var(--text-secondary)] mb-8">Accepted by leading insurers</p>
    <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-4 text-[var(--text)]/70 font-bold text-lg tracking-tight">
      <span>Daman</span>
      <span>Thiqa</span>
      <span>ADNIC</span>
      <span>AXA</span>
      <span>NextCare</span>
    </div>
  </div>
</section>

<!-- Services -->
<section id="services" class="py-16 md:py-24">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center mb-16">
      <span class="eyebrow"><span class="dot"></span> Services</span>
      <h2 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">Care built around you</h2>
      <p class="mt-4 text-[var(--text-secondary)] text-lg">Six focused programs, one integrated clinical team.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div class="card p-7"><div class="icon-box">🧑‍⚕️</div><h4 class="font-bold text-lg mt-5 mb-1.5">Individual Therapy</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">One-on-one sessions using CBT, DBT, and mindfulness — tailored to your goals.</p></div>
      <div class="card p-7"><div class="icon-box">👨‍👩‍👧</div><h4 class="font-bold text-lg mt-5 mb-1.5">Family Therapy</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Rebuild communication and strengthen the relationships that matter most.</p></div>
      <div class="card p-7"><div class="icon-box">💑</div><h4 class="font-bold text-lg mt-5 mb-1.5">Couples Counseling</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Evidence-based approaches to deepen connection and resolve conflict.</p></div>
      <div class="card p-7"><div class="icon-box">🧩</div><h4 class="font-bold text-lg mt-5 mb-1.5">ABA Therapy</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">1:1 programs from 10–30 hrs/week, supervised by certified BCBAs.</p></div>
      <div class="card p-7"><div class="icon-box">🌱</div><h4 class="font-bold text-lg mt-5 mb-1.5">Speech & OT</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Supporting communication, sensory processing, and motor development.</p></div>
      <div class="card p-7"><div class="icon-box">📋</div><h4 class="font-bold text-lg mt-5 mb-1.5">Diagnostic Assessment</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Comprehensive ADOS-2, cognitive, and behavioral evaluations.</p></div>
    </div>
  </div>
</section>

<!-- How it works -->
<section id="showcase" class="py-16 md:py-24 bg-[var(--bg-secondary)] border-y border-[var(--border)]">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center mb-16">
      <span class="eyebrow"><span class="dot"></span> How it works</span>
      <h2 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">From booking to breakthrough</h2>
      <p class="mt-4 text-[var(--text-secondary)] text-lg">Three steps, no waiting rooms full of paperwork.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 relative">
      <div class="hidden lg:block absolute top-6 left-[16.6%] right-[16.6%] h-px bg-[var(--border)]"></div>
      <div class="card p-8 relative bg-white"><div class="w-9 h-9 rounded-full bg-[var(--accent)] text-white flex items-center justify-center font-bold text-sm mb-5">1</div><h4 class="font-bold text-lg mb-2">Pick your specialist</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Browse therapist profiles and match by specialty, language, or availability.</p></div>
      <div class="card p-8 relative bg-white"><div class="w-9 h-9 rounded-full bg-[var(--accent)] text-white flex items-center justify-center font-bold text-sm mb-5">2</div><h4 class="font-bold text-lg mb-2">Book instantly</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Choose a slot that works, confirm insurance coverage, and get instant confirmation.</p></div>
      <div class="card p-8 relative bg-white"><div class="w-9 h-9 rounded-full bg-[var(--accent)] text-white flex items-center justify-center font-bold text-sm mb-5">3</div><h4 class="font-bold text-lg mb-2">Start your sessions</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Meet in-clinic or via telehealth — track progress with your therapist over time.</p></div>
    </div>
  </div>
</section>

<!-- Benefits -->
<section class="py-16 md:py-24">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center mb-16">
      <span class="eyebrow"><span class="dot"></span> Why Engage Clinic</span>
      <h2 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">Care that's actually accessible</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <div class="card p-6"><div class="icon-box">🧠</div><h4 class="font-semibold mt-5 mb-1.5">Experienced team</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Licensed, evidence-based clinicians across every discipline.</p></div>
      <div class="card p-6"><div class="icon-box">🛡️</div><h4 class="font-semibold mt-5 mb-1.5">Fully confidential</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Private rooms, secure records, strict clinical confidentiality.</p></div>
      <div class="card p-6"><div class="icon-box">💳</div><h4 class="font-semibold mt-5 mb-1.5">Insurance-friendly</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">Direct billing with Daman, Thiqa, ADNIC, AXA, and more.</p></div>
      <div class="card p-6"><div class="icon-box">🕊️</div><h4 class="font-semibold mt-5 mb-1.5">Calm environment</h4><p class="text-[var(--text-secondary)] text-sm leading-relaxed">A relaxed, welcoming clinic designed for real comfort.</p></div>
    </div>
  </div>
</section>

<!-- Stats -->
<section class="py-12 md:py-16 border-y border-[var(--border)] bg-[var(--bg-secondary)]">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
      <div class="text-center"><div class="stat-number">200+</div><div class="stat-label mt-1">Happy families</div></div>
      <div class="text-center"><div class="stat-number">5</div><div class="stat-label mt-1">Expert therapists</div></div>
      <div class="text-center"><div class="stat-number">95%</div><div class="stat-label mt-1">Satisfaction rate</div></div>
      <div class="text-center"><div class="stat-number">8</div><div class="stat-label mt-1">Service types</div></div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section id="testimonials" class="py-16 md:py-24">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center mb-16">
      <span class="eyebrow"><span class="dot"></span> Testimonials</span>
      <h2 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">What clients tell us</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div class="card p-7"><div class="flex text-[var(--accent)] text-sm mb-4">★★★★★</div><p class="text-[var(--text)] text-sm leading-relaxed">"Engage Clinic gave me the tools to manage my anxiety. I feel like myself again."</p><p class="font-semibold mt-5 text-sm text-[var(--text-secondary)]">Noora, Umm Rashid</p></div>
      <div class="card p-7"><div class="flex text-[var(--accent)] text-sm mb-4">★★★★★</div><p class="text-[var(--text)] text-sm leading-relaxed">"Warm, professional, and truly life-changing. Highly recommend their therapy."</p><p class="font-semibold mt-5 text-sm text-[var(--text-secondary)]">Fatima Al Shamsi</p></div>
      <div class="card p-7"><div class="flex text-[var(--accent)] text-sm mb-4">★★★★★</div><p class="text-[var(--text)] text-sm leading-relaxed">"Our son has made incredible progress since starting ABA therapy. The team is amazing!"</p><p class="font-semibold mt-5 text-sm text-[var(--text-secondary)]">Reem Al Falasi</p></div>
    </div>
  </div>
</section>

<!-- Team -->
<section id="team" class="py-16 md:py-24 bg-[var(--bg-secondary)] border-y border-[var(--border)]">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="max-w-2xl mx-auto text-center mb-16">
      <span class="eyebrow"><span class="dot"></span> Our team</span>
      <h2 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">Meet your therapists</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 max-w-4xl mx-auto">
      <div class="card p-7 text-center bg-white">
        <div class="w-20 h-20 rounded-2xl mx-auto overflow-hidden photo-wrap border border-[var(--border)]">
          <img src="https://images.unsplash.com/photo-1758691462651-611d730c5272?fm=jpg&q=80&w=400&auto=format&fit=crop" alt="Dr. Fatima Zahra" class="w-full h-full object-cover muted-photo">
        </div>
        <h4 class="font-bold text-lg mt-5">Dr. Fatima Zahra</h4>
        <p class="text-[var(--accent-hover)] text-sm font-semibold">Clinical Psychologist</p>
        <p class="text-[var(--text-secondary)] text-sm mt-2 leading-relaxed">12+ years in trauma & anxiety, CBT/DBT certified.</p>
      </div>
      <div class="card p-7 text-center bg-white">
        <div class="w-20 h-20 rounded-2xl mx-auto overflow-hidden photo-wrap border border-[var(--border)]">
          <img src="https://images.unsplash.com/photo-1651684215020-f7a5b6610f23?fm=jpg&q=80&w=400&auto=format&fit=crop" alt="Mr. James Okafor" class="w-full h-full object-cover muted-photo">
        </div>
        <h4 class="font-bold text-lg mt-5">Mr. James Okafor</h4>
        <p class="text-[var(--accent-hover)] text-sm font-semibold">Licensed Therapist</p>
        <p class="text-[var(--text-secondary)] text-sm mt-2 leading-relaxed">Specializes in family therapy & mindfulness-based approaches.</p>
      </div>
      <div class="card p-7 text-center bg-white">
        <div class="w-20 h-20 rounded-2xl mx-auto overflow-hidden photo-wrap border border-[var(--border)]">
          <img src="https://images.unsplash.com/photo-1758518727888-ffa196002e59?fm=jpg&q=80&w=400&auto=format&fit=crop" alt="Ms. Hanan Youssef" class="w-full h-full object-cover muted-photo">
        </div>
        <h4 class="font-bold text-lg mt-5">Ms. Hanan Youssef</h4>
        <p class="text-[var(--accent-hover)] text-sm font-semibold">ABA Therapist (RBT)</p>
        <p class="text-[var(--text-secondary)] text-sm mt-2 leading-relaxed">Specializes in ABA therapy & early intervention for children.</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="py-16 md:py-24">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <div class="text-center mb-14">
      <span class="eyebrow"><span class="dot"></span> FAQ</span>
      <h2 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">Common questions</h2>
    </div>
    <div class="space-y-3">
      <details class="faq-item card p-6" open>
        <summary class="flex items-center justify-between font-semibold text-base">Do you accept insurance?<span class="icon-box w-8 h-8 text-base">+</span></summary>
        <p class="text-[var(--text-secondary)] text-sm mt-4 leading-relaxed">Yes — we bill directly with Daman, Thiqa, ADNIC, AXA, and NextCare. Bring your insurance card to your first visit, or send it ahead when booking.</p>
      </details>
      <details class="faq-item card p-6">
        <summary class="flex items-center justify-between font-semibold text-base">How do I choose the right therapist?<span class="icon-box w-8 h-8 text-base">+</span></summary>
        <p class="text-[var(--text-secondary)] text-sm mt-4 leading-relaxed">Tell us what you're looking for when you book and we'll match you based on specialty, language, and availability. You can switch therapists at any time.</p>
      </details>
      <details class="faq-item card p-6">
        <summary class="flex items-center justify-between font-semibold text-base">Is telehealth available?<span class="icon-box w-8 h-8 text-base">+</span></summary>
        <p class="text-[var(--text-secondary)] text-sm mt-4 leading-relaxed">Most services are available both in-clinic and via secure video sessions — pick whichever fits your week when you book.</p>
      </details>
      <details class="faq-item card p-6">
        <summary class="flex items-center justify-between font-semibold text-base">What's your cancellation policy?<span class="icon-box w-8 h-8 text-base">+</span></summary>
        <p class="text-[var(--text-secondary)] text-sm mt-4 leading-relaxed">You can reschedule or cancel free of charge up to 24 hours before your session. Later changes may incur a small fee.</p>
      </details>
    </div>
  </div>
</section>

<!-- CTA banner -->
<section class="py-6">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="relative overflow-hidden rounded-3xl border border-[var(--border)] bg-[var(--bg-secondary)] px-6 sm:px-8 py-12 sm:py-16 text-center">
      <div class="glow w-[480px] h-[480px] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-40"></div>
      <div class="relative">
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight">Ready to feel like yourself again?</h2>
        <p class="text-[var(--text-secondary)] mt-4 max-w-md mx-auto">Book your first session today — most clients are seen within the week.</p>
        <a href="#appointment" class="btn-primary inline-block text-base px-8 py-3.5 mt-8">Book a session →</a>
      </div>
    </div>
  </div>
</section>

<!-- Appointment + Contact - New Lead Style -->
<section id="appointment" class="py-16 md:py-24">
  <div class="max-w-6xl mx-auto px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-12">
    <!-- Booking Form - Lead Style -->
    <div class="card p-8">
      <!-- Header -->
      <div class="mb-6">
        <span class="eyebrow"><span class="dot"></span> New lead</span>
        <h3 class="mt-3 text-xl font-extrabold tracking-tight">Manual entry</h3>
        <p class="text-sm text-[var(--text-secondary)] mt-1">Walk-in, phone call, or event enquiry.</p>
      </div>

      <!-- Success Message -->
      <div id="successMessage" style="display:none;background:var(--accent-soft);color:var(--accent-hover);padding:12px 14px;border-radius:10px;margin-bottom:15px;font-size:13.5px;font-weight:600;border:1px solid #B7F2FA;">
        ✅ Lead saved successfully!
      </div>

      <!-- Error Message -->
      <div id="errorMessage" style="display:none;background:#FEF2F2;color:#B91C1C;padding:12px 14px;border-radius:10px;margin-bottom:15px;font-size:13.5px;font-weight:600;border:1px solid #FECACA;">
        ❌ Please fill in all required fields.
      </div>

      <!-- Form -->
      <form id="leadForm" class="space-y-4" onsubmit="submitLead(event)">
        <!-- CSRF Token for Laravel -->
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <!-- Child Name & Age -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_110px] gap-3">
          <div>
            <label class="crm-label">Child's name *</label>
            <input type="text" id="child_name" name="child_name" placeholder="e.g. Hamad" class="crm-input" required>
          </div>
          <div>
            <label class="crm-label">Age</label>
            <input type="text" id="child_age" name="child_age" placeholder="5" class="crm-input">
          </div>
        </div>

        <!-- Parent & Phone -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="crm-label">Parent / guardian *</label>
            <input type="text" id="parent_guardian_name" name="parent_guardian_name" placeholder="e.g. Mrs. Shamma Al Qubaisi" class="crm-input" required>
          </div>
          <div>
            <label class="crm-label">Phone (WhatsApp)</label>
            <input type="tel" id="phone" name="phone" placeholder="+971 5x xxx xxxx" class="crm-input">
          </div>
        </div>

        <!-- Source & Interested In -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="crm-label">Source</label>
            <select id="source" name="source" class="crm-select">
              <option value="Walk-in">Walk-in</option>
              <option value="Phone call">Phone call</option>
              <option value="WhatsApp">WhatsApp</option>
              <option value="Website">Website</option>
              <option value="Instagram">Instagram</option>
              <option value="Referral">Referral</option>
              <option value="Google">Google</option>
              <option value="Event">Event</option>
            </select>
          </div>
          <div>
            <label class="crm-label">Interested in</label>
            <select id="interested_in" name="interested_in" class="crm-select">
              <option value="ABA therapy">ABA therapy</option>
              <option value="Speech therapy">Speech therapy</option>
              <option value="Occupational therapy">Occupational therapy</option>
              <option value="Diagnostic assessment">Diagnostic assessment</option>
              <option value="Early intervention">Early intervention</option>
              <option value="Combined program">Combined program</option>
            </select>
          </div>
        </div>

        <!-- Insurance & Est. Value -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="crm-label">Insurance</label>
            <select id="insurance" name="insurance" class="crm-select">
              <option value="Not sure yet">Not sure yet</option>
              <option value="Daman">Daman</option>
              <option value="Daman Enhanced">Daman Enhanced</option>
              <option value="Thiqa">Thiqa</option>
              <option value="ADNIC">ADNIC</option>
              <option value="AXA / GIG">AXA / GIG</option>
              <option value="Self-pay">Self-pay</option>
            </select>
          </div>
          <div>
            <label class="crm-label">Est. monthly value (AED)</label>
            <input type="text" id="estimated_value" name="estimated_value" placeholder="12,800" class="crm-input">
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="crm-label">Notes</label>
          <input type="text" id="notes" name="notes" placeholder="e.g. asked about fees and Daman coverage" class="crm-input">
        </div>

        <!-- Buttons -->
        <div style="display:flex;gap:10px;margin-top:8px;">
          <button type="submit" class="btn-primary" style="flex:1;border:none;cursor:pointer;">
            Save lead
          </button>
          <button type="button" onclick="resetForm()" class="btn-outline" style="width:120px;cursor:pointer;">
            Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Contact Side with Google Maps -->
    <div id="contact" class="space-y-6">
      <span class="eyebrow"><span class="dot"></span> Contact</span>
      <div>
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight">Get in touch</h2>
        <p class="text-[var(--text-secondary)] mt-2 text-sm leading-relaxed max-w-sm">Prefer to talk it through first? Reach us directly — we usually reply within the hour during clinic hours.</p>
      </div>
      
      <!-- Google Maps Iframe -->
      <div class="map-container" style="min-height:280px;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1455.8235078452736!2d54.3810513!3d24.4960328!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x824f161b36042481%3A0xb0645c43efcb1801!2sEngage%20Behavioral%20Learning%20Abilitation%20Center!5e0!3m2!1sen!2sae!4v1739475374654!5m2!1sen!2sae" 
          width="100%" 
          height="100%" 
          style="border:0;min-height:280px;" 
          allowfullscreen="" 
          loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade"
          title="Engage Clinic Location">
        </iframe>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="border-t border-[var(--border)] pt-16 pb-8">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-10">
    <div>
      <a href="/" class="flex items-center gap-2 font-bold text-lg tracking-tight">
        <span class="bg-white rounded-lg p-1.5 flex items-center justify-center">
          <img src="/uploads/engage.png" alt="Engage Clinic logo" class="h-9 w-auto object-contain">
        </span>
      </a>
      <p class="text-xs text-[var(--text-secondary)] mt-3">Therapy · Growth · Well-being</p>
    </div>
    <div><h6 class="font-semibold mb-3 text-sm">Quick links</h6><ul class="text-sm space-y-2 text-[var(--text-secondary)]"><li><a href="#services">Services</a></li><li><a href="#showcase">How it works</a></li><li><a href="#team">Therapists</a></li><li><a href="#faq">FAQ</a></li></ul></div>
    <div><h6 class="font-semibold mb-3 text-sm">Services</h6><ul class="text-sm space-y-2 text-[var(--text-secondary)]"><li>Individual Therapy</li><li>Family Therapy</li><li>ABA Therapy</li><li>Speech & OT</li></ul></div>
    <div><h6 class="font-semibold mb-3 text-sm">Contact</h6><p class="text-sm text-[var(--text-secondary)]">hello@engageclinic.ae</p><p class="text-sm text-[var(--text-secondary)] mt-1">(971) 50 123 4567</p></div>
  </div>
  <div class="max-w-7xl mx-auto px-6 lg:px-8 border-t border-[var(--border)] mt-10 pt-6 text-xs text-[var(--text-secondary)] flex flex-col md:flex-row justify-between gap-2">
    <span>© 2026 Engage Clinic. All rights reserved.</span>
    <span>Made for mental wellness</span>
  </div>
</footer>

<script>
  // Mobile menu toggle
  const menuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (menuBtn && mobileMenu) {
    menuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
    document.querySelectorAll('#mobile-menu a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.add('hidden')));
  }

  // FAQ accordion - only one open at a time
  document.querySelectorAll('.faq-item').forEach(item => {
    item.addEventListener('toggle', () => {
      if (item.open) {
        document.querySelectorAll('.faq-item').forEach(other => { 
          if (other !== item) other.open = false; 
        });
      }
    });
  });

  // Submit lead form
  function submitLead(event) {
    event.preventDefault();
    
    // Get form data
    const formData = new FormData(document.getElementById('leadForm'));
    
    // Show loading state on button
    const submitBtn = document.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    // Hide previous messages
    document.getElementById('successMessage').style.display = 'none';
    document.getElementById('errorMessage').style.display = 'none';
    
    // Send to Laravel API
    fetch('/api/leads', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        'Accept': 'application/json',
      },
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success message
        document.getElementById('successMessage').style.display = 'block';
        document.getElementById('successMessage').textContent = '✅ ' + data.message;
        
        // Reset form after successful save
        setTimeout(() => {
          resetForm();
          // Hide success message after 5 seconds
          setTimeout(() => {
            document.getElementById('successMessage').style.display = 'none';
          }, 5000);
        }, 1000);
      } else {
        // Show error message
        document.getElementById('errorMessage').style.display = 'block';
        if (data.errors) {
          const errors = Object.values(data.errors).flat().join(', ');
          document.getElementById('errorMessage').textContent = '❌ ' + errors;
        } else {
          document.getElementById('errorMessage').textContent = '❌ ' + (data.message || 'Something went wrong. Please try again.');
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById('errorMessage').style.display = 'block';
      document.getElementById('errorMessage').textContent = '❌ Network error. Please check your connection and try again.';
    })
    .finally(() => {
      // Restore button state
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    });
  }

  // Reset form function
  function resetForm() {
    document.getElementById('leadForm').reset();
    document.getElementById('successMessage').style.display = 'none';
    document.getElementById('errorMessage').style.display = 'none';
    
    // Reset any validation states
    const inputs = document.querySelectorAll('#leadForm input, #leadForm select');
    inputs.forEach(input => {
      input.style.borderColor = '#E2DACE';
    });
  }

  // Real-time validation on input
  document.querySelectorAll('#leadForm input, #leadForm select').forEach(input => {
    input.addEventListener('blur', function() {
      if (this.hasAttribute('required') && !this.value.trim()) {
        this.style.borderColor = '#dc3545';
      } else {
        this.style.borderColor = '#E2DACEB';
      }
    });
    
    input.addEventListener('input', function() {
      if (this.style.borderColor === '#dc3545' && this.value.trim()) {
        this.style.borderColor = '#28a745';
      }
    });
  });
</script>
</body>
</html>

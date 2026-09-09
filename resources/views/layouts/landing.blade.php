<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Engage Clinic · ABA Therapy Built Around Your Child')</title>
<link rel="icon" type="image/png" href="{{ asset('uploads/engage.png') }}">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#FFFF;
    --dot:rgba(223,218,205,0.5);
    --card:#FFFFFF;
    --border:#E2DACE;
    --navy:#16436E;
    --navy-deep:#0E2E4C;
    --navy-soft:#EAF1F8;
    --pink:#C8355F;
    --pink-hover:#A82348;
    --pink-soft:#FCEAF0;
    --pink-mid:#F2A8C6;
    --pink-border:#F6C9D6;
    --maroon:#9C2A55;
    --indigo:#5B5FE0;
    --lime:#C6DB2E;
    --green:#1E8A4C;
    --orange:#F5A623;
    --gold:#F2C230;
    --text:#2B2A27;
    --text-secondary:#5A6B7E;
    --text-muted:#98897A;
    --whatsapp:#25D366;
    --whatsapp-hover:#1FBE5A;
    --radius:20px;
  }
  *{font-family:'Inter',system-ui,-apple-system,sans-serif;}
  body{background:var(--bg);color:var(--text);}
  .display{font-family:'Baloo 2',system-ui,sans-serif;}

  .dotted{
    background-image:radial-gradient(var(--dot) 1.5px, transparent 1.5px);
    background-size:24px 24px;
  }

  .eyebrow{
    display:inline-flex;align-items:center;gap:7px;
    background:var(--pink-soft);color:var(--pink);
    border:1px solid var(--pink-border);
    font-size:12.5px;font-weight:700;letter-spacing:.01em;
    padding:7px 14px;border-radius:999px;
  }
  .eyebrow-plain{
    display:block;color:var(--pink);font-size:12.5px;font-weight:800;
    letter-spacing:.12em;text-transform:uppercase;
  }

  .btn-pink{
    background:var(--pink);color:#fff;
    border-radius:999px;font-weight:700;font-size:15px;
    padding:13px 26px;display:inline-flex;align-items:center;gap:8px;
    transition:background-color .15s, transform .15s, box-shadow .15s;
  }
  .btn-pink:hover{background:var(--pink-hover);transform:translateY(-1px);box-shadow:0 10px 22px -10px rgba(200,53,95,.6);}

  .btn-ghost{
    background:#fff;color:var(--navy);border:1px solid var(--border);
    border-radius:999px;font-weight:700;font-size:15px;
    padding:13px 26px;display:inline-flex;align-items:center;gap:8px;
    transition:border-color .15s, background-color .15s;
  }
  .btn-ghost:hover{border-color:var(--pink);background:var(--pink-soft);}

  .btn-white{
    background:#fff;color:var(--pink);border:none;
    border-radius:999px;font-weight:700;font-size:15px;
    padding:13px 26px;display:inline-flex;align-items:center;gap:8px;
    transition:transform .15s, box-shadow .15s;
  }
  .btn-white:hover{transform:translateY(-1px);box-shadow:0 10px 22px -10px rgba(0,0,0,.35);}

  .btn-outline-white{
    background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.55);
    border-radius:999px;font-weight:700;font-size:15px;
    padding:12px 24px;display:inline-flex;align-items:center;gap:8px;
    transition:background-color .15s, border-color .15s;
  }
  .btn-outline-white:hover{background:rgba(255,255,255,.12);border-color:#fff;}

  .btn-whatsapp{
    background:var(--whatsapp);color:#fff;border-radius:999px;
    font-weight:700;font-size:13.5px;padding:10px 18px;
    display:inline-flex;align-items:center;gap:6px;
    transition:background-color .15s;
  }
  .btn-whatsapp:hover{background:var(--whatsapp-hover);}

  .card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);}

  .nav-link{color:var(--navy);font-size:14.5px;font-weight:600;padding-bottom:4px;border-bottom:2px solid transparent;}
  .nav-link:hover{color:var(--pink);}
  .nav-link.active{color:var(--pink);border-color:var(--pink);}

  .trust-pill{
    display:inline-flex;align-items:center;gap:6px;
    background:#fff;border:1px solid var(--border);border-radius:999px;
    padding:7px 14px;font-size:12.5px;font-weight:600;color:var(--navy);
  }
  .trust-pill svg{color:var(--pink);flex-shrink:0;}
  .trust-pill.on-dark{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff;}
  .trust-pill.on-dark svg{color:#fff;}

  /* Hero gallery — synced, animated carousel */
  .hero-gallery{display:flex;gap:14px;overflow:hidden;height:clamp(420px,54vw,620px);}

  /* Hero copy crossfade — snappy exit, eased+staggered entrance */
  #heroEyebrow, #heroHeading, #heroParagraph, #heroBookBtn{will-change:opacity, transform;}
  #heroEyebrow:not(.hero-copy-fade){transition:opacity .5s cubic-bezier(.22,.61,.36,1), transform .5s cubic-bezier(.22,.61,.36,1), background-color .35s ease, border-color .35s ease, color .35s ease;}
  #heroHeading:not(.hero-copy-fade){transition:opacity .55s cubic-bezier(.22,.61,.36,1) .06s, transform .55s cubic-bezier(.22,.61,.36,1) .06s;}
  #heroParagraph:not(.hero-copy-fade){transition:opacity .55s cubic-bezier(.22,.61,.36,1) .12s, transform .55s cubic-bezier(.22,.61,.36,1) .12s;}
  #heroBookBtn:not(.hero-copy-fade){transition:opacity .5s cubic-bezier(.22,.61,.36,1) .18s, transform .5s cubic-bezier(.22,.61,.36,1) .18s, background-color .35s ease;}
  .hero-copy-fade{opacity:0;transform:translateY(10px);transition:opacity .2s ease-in, transform .2s ease-in !important;}
  @media (prefers-reduced-motion: reduce){
    #heroEyebrow, #heroHeading, #heroParagraph, #heroBookBtn{transition:none !important;}
    .hero-copy-fade{transform:none;}
  }

  .hero-slide-item{
    position:relative;flex:1 1 0%;min-width:0;border-radius:24px;overflow:hidden;cursor:pointer;
    transition:flex-grow .55s cubic-bezier(.4,0,.2,1);
  }
  .hero-slide-item.is-active{flex:6 1 0%;cursor:default;}
  .hero-slide-item img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
  .hero-slide-item .scrim{position:absolute;inset:0;background:linear-gradient(0deg,rgba(14,46,76,.82) 0%,rgba(14,46,76,.3) 40%,rgba(14,46,76,0) 62%);transition:opacity .4s ease;}
  .hero-slide-item:not(.is-active) .scrim{background:linear-gradient(0deg,rgba(14,46,76,.75) 0%,rgba(14,46,76,0) 45%);}

  .item-badge{
    position:absolute;left:12px;top:14px;z-index:3;color:#fff;font-size:11px;font-weight:700;
    padding:6px 12px;border-radius:999px;white-space:nowrap;
    transition:top .5s cubic-bezier(.4,0,.2,1), font-size .3s ease, padding .3s ease;
  }
  .hero-slide-item:not(.is-active) .item-badge{top:calc(100% - 34px);font-size:10.5px;padding:4px 10px;}

  .strip-label{
    position:absolute;left:12px;bottom:56px;color:#fff;font-weight:700;font-size:11.5px;
    writing-mode:vertical-rl;transform:rotate(180deg);letter-spacing:.01em;z-index:2;
    opacity:1;transition:opacity .25s ease;
  }
  .hero-slide-item.is-active .strip-label{opacity:0;}

  .main-content{position:absolute;left:18px;right:18px;bottom:38px;z-index:2;color:#fff;opacity:0;pointer-events:none;transition:opacity .35s ease .15s;}
  .hero-slide-item.is-active .main-content{opacity:1;pointer-events:auto;}
  .main-content h4{font-size:17px;font-weight:700;}
  .main-content p{font-size:12px;color:rgba(255,255,255,.78);margin-top:4px;line-height:1.5;}
  .main-content a{font-size:12px;font-weight:700;color:#fff;display:inline-flex;align-items:center;gap:4px;margin-top:8px;}

  .tag-pill{position:absolute;left:10px;bottom:12px;z-index:2;font-size:10.5px;font-weight:700;padding:4px 10px;border-radius:999px;white-space:nowrap;}
  .tag-blue{background:#E3EEFC;color:#2563A8;}
  .tag-green{background:#E4F6EB;color:#1E8A4C;}
  .tag-orange{background:#FDECDD;color:#C2650A;}
  .tag-lime{background:var(--lime);color:var(--navy-deep);}

  .carousel-btn{width:36px;height:36px;border-radius:999px;border:1px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;color:var(--navy);transition:border-color .15s,color .15s;flex-shrink:0;}
  .carousel-btn:hover{border-color:var(--pink);color:var(--pink);}
  .carousel-dot{width:8px;height:8px;border-radius:999px;background:var(--border);transition:all .2s;cursor:pointer;border:none;padding:0;}
  .carousel-dot.active{width:22px;background:var(--pink);}

  .stat-number{font-family:'Baloo 2',sans-serif;font-size:2rem;font-weight:800;color:var(--pink);}
  @media (min-width:640px){ .stat-number{font-size:2.5rem;} }
  .stat-label{font-size:13px;color:var(--text-secondary);font-weight:600;margin-top:2px;}

  /* Programmes bento */
  .prog-card{position:relative;border-radius:24px;overflow:hidden;color:#fff;display:flex;flex-direction:column;justify-content:flex-end;padding:22px;min-height:180px;}
  .prog-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;}
  .prog-card .scrim{position:absolute;inset:0;background:linear-gradient(0deg,rgba(14,46,76,.85) 0%,rgba(14,46,76,.15) 55%,transparent 75%);z-index:1;}
  .prog-card > *{position:relative;z-index:2;}
  .prog-icon{width:38px;height:38px;border-radius:11px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);display:flex;align-items:center;justify-content:center;color:#fff;margin-bottom:14px;}
  .prog-label{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;opacity:.85;}
  .prog-title{font-family:'Baloo 2',sans-serif;font-size:1.35rem;font-weight:700;margin-top:4px;line-height:1.15;}
  .prog-watermark{position:absolute;right:-6px;bottom:-24px;font-family:'Baloo 2',sans-serif;font-weight:800;font-size:6.5rem;color:rgba(255,255,255,.10);z-index:1;line-height:1;pointer-events:none;}

  /* GIFTED tiles */
  .gifted-tile{border-radius:22px;padding:34px 22px;text-align:center;display:flex;flex-direction:column;align-items:center;}
  .gifted-letter{font-family:'Baloo 2',sans-serif;font-weight:800;font-size:2.75rem;line-height:1;}
  .gifted-title{font-weight:700;font-size:15px;margin-top:10px;}
  .gifted-desc{font-size:12.5px;margin-top:6px;line-height:1.5;opacity:.85;max-width:200px;}

  /* Enrich grid */
  .enrich-item{background:var(--bg);border-radius:16px;padding:18px;}
  .enrich-dot{width:9px;height:9px;border-radius:999px;display:inline-block;margin-bottom:10px;}
  .enrich-item h4{font-weight:700;color:var(--navy);font-size:14.5px;}
  .enrich-item p{font-size:12.5px;color:var(--text-secondary);margin-top:4px;line-height:1.5;}

  .accred-badge{width:110px;height:110px;border-radius:999px;border:3px solid var(--navy);display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--navy);background:#fff;font-weight:800;font-size:11px;letter-spacing:.03em;text-align:center;line-height:1.3;padding:10px;}
  .accred-badge.shield{border-radius:16px 16px 40% 40%;border-color:var(--gold);background:linear-gradient(160deg,#fff,#FFF7E2);}

  .icon-box{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:var(--pink-soft);color:var(--pink);font-size:20px;border:1px solid var(--pink-border);flex-shrink:0;}

  .marquee{overflow:hidden;-webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);}
  .marquee-track{display:flex;width:max-content;animation:marquee 26s linear infinite;}
  .marquee:hover .marquee-track{animation-play-state:paused;}
  @keyframes marquee{from{transform:translateX(0);}to{transform:translateX(-50%);}}
  .marquee-item{font-family:'Baloo 2',sans-serif;font-weight:700;font-size:1.1rem;color:var(--navy);opacity:.55;padding:0 2.25rem;white-space:nowrap;}

  .crm-input{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--text);transition:all .15s;outline:none;}
  .crm-input:focus{border-color:var(--pink);box-shadow:0 0 0 3px rgba(200,53,95,.1);}
  .crm-select{width:100%;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--text);outline:none;transition:all .15s;}
  .crm-select:focus{border-color:var(--pink);box-shadow:0 0 0 3px rgba(200,53,95,.1);}
  .crm-label{font-size:11px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;display:block;}

  /* Service detail rows */
  .service-badge{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;padding:6px 14px;border-radius:999px;}
  .service-check{display:flex;align-items:flex-start;gap:8px;font-size:13.5px;color:var(--text-secondary);font-weight:500;}
  .service-check svg{flex-shrink:0;margin-top:2px;}

  /* Footer */
  .footer-dark{background:var(--navy-deep);color:rgba(255,255,255,.85);}
  .footer-heading{color:var(--pink);font-size:11.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;margin-bottom:14px;}
  .footer-link{color:rgba(255,255,255,.72);font-size:14px;transition:color .15s;}
  .footer-link:hover{color:#fff;}
  .social-btn{width:34px;height:34px;border-radius:999px;background:rgba(200,53,95,.18);border:1px solid rgba(200,53,95,.35);display:flex;align-items:center;justify-content:center;color:var(--pink-mid);transition:background-color .15s;}
  .social-btn:hover{background:var(--pink);color:#fff;}

  /* Floating soft-opening widget */
  .float-widget{position:fixed;right:18px;bottom:18px;z-index:50;width:min(300px,calc(100vw - 36px));}
  .float-toggle{
    background:var(--pink);color:#fff;border-radius:999px;border:none;cursor:pointer;
    padding:10px 16px;font-size:12px;font-weight:700;
    box-shadow:0 14px 28px -10px rgba(200,53,95,.55);
    display:flex;align-items:center;justify-content:center;gap:6px;width:100%;
  }
  .float-panel{
    margin-top:8px;background:var(--navy-deep);color:#fff;border-radius:18px;padding:18px;
    box-shadow:0 20px 40px -12px rgba(0,0,0,.4);display:none;
  }
  .float-panel.open{display:block;}
  .float-panel p{font-size:12.5px;color:rgba(255,255,255,.75);line-height:1.5;}
  .countdown-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:14px 0;}
  .countdown-box{background:rgba(255,255,255,.08);border-radius:10px;padding:10px 4px;text-align:center;}
  .countdown-box .num{font-family:'Baloo 2',sans-serif;font-weight:800;font-size:1.35rem;color:var(--gold);}
  .countdown-box .unit{font-size:9.5px;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.05em;margin-top:2px;}
  .float-address{font-size:11px;color:rgba(255,255,255,.55);margin-top:4px;line-height:1.5;}

  /* Booking modal */
  .modal-overlay{
    position:fixed;inset:0;z-index:70;display:none;align-items:center;justify-content:center;
    background:rgba(14,46,76,.55);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
    padding:20px;
  }
  .modal-overlay.open{display:flex;}
  .modal-card{
    background:#fff;border-radius:22px;width:100%;max-width:600px;max-height:90vh;
    display:flex;flex-direction:column;overflow:hidden;box-shadow:0 30px 60px -15px rgba(0,0,0,.4);
  }
  .modal-header{
    background:var(--navy-deep);color:#fff;padding:22px 26px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-shrink:0;
  }
  .modal-header h3{font-size:1.3rem;font-weight:700;}
  .modal-header p{font-size:12.5px;color:rgba(255,255,255,.65);margin-top:4px;}
  .modal-close{width:30px;height:30px;border-radius:999px;background:rgba(255,255,255,.12);border:none;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;}
  .modal-close:hover{background:rgba(255,255,255,.22);}

  .modal-stepper{display:flex;align-items:center;gap:10px;padding:16px 26px;background:var(--bg);border-bottom:1px solid var(--border);flex-shrink:0;overflow-x:auto;}
  .step-node{display:flex;align-items:center;gap:8px;flex-shrink:0;}
  .step-circle{width:26px;height:26px;border-radius:999px;background:var(--border);color:var(--text-secondary);font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background-color .2s,color .2s;}
  .step-label{font-size:12.5px;font-weight:700;color:var(--text-muted);white-space:nowrap;}
  .step-line{width:28px;height:2px;background:var(--border);flex-shrink:0;}
  .step-node.done .step-circle{background:var(--pink);color:#fff;}
  .step-node.done .step-label{color:var(--navy);}
  .step-node.active .step-circle{background:var(--pink);color:#fff;}
  .step-node.active .step-label{color:var(--navy);}

  .modal-body{padding:24px 26px 26px;overflow-y:auto;}
  .modal-step.hidden{display:none;}
  .modal-step-title{font-size:1.1rem;font-weight:700;color:var(--navy);}
  .modal-step-subtitle{font-size:12.5px;color:var(--text-muted);margin-top:2px;}
  .step-back{width:32px;height:32px;border-radius:999px;border:1px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;color:var(--navy);margin-bottom:12px;cursor:pointer;}
  .step-back:hover{border-color:var(--pink);color:var(--pink);}

  .cal-nav{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
  .cal-nav span{font-weight:700;color:var(--navy);font-size:15px;}
  .cal-weekdays{display:grid;grid-template-columns:repeat(7,1fr);text-align:center;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:6px;}
  .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;}
  .cal-day{aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:10px;font-size:13px;font-weight:600;color:var(--navy);cursor:pointer;border:1.5px solid transparent;background:transparent;}
  .cal-day:hover:not(.disabled):not(.selected){background:var(--pink-soft);}
  .cal-day.faded{color:var(--border);pointer-events:none;}
  .cal-day.disabled{color:var(--border);pointer-events:none;text-decoration:line-through;}
  .cal-day.today{border-color:var(--pink);color:var(--pink);}
  .cal-day.selected{background:var(--pink);color:#fff;}
  .cal-legend{display:flex;gap:18px;margin-top:16px;font-size:12px;color:var(--text-secondary);}
  .cal-legend .swatch{display:inline-block;width:12px;height:12px;border-radius:4px;margin-right:5px;vertical-align:-2px;}
  .cal-legend .swatch.today{border:1.5px solid var(--pink);}
  .cal-legend .swatch.unavailable{background:var(--border);}

  .time-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
  @media (min-width:480px){ .time-grid{grid-template-columns:repeat(4,1fr);} }
  .time-slot{background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:11px 6px;text-align:center;font-size:13.5px;font-weight:600;color:var(--navy);cursor:pointer;}
  .time-slot:hover{border-color:var(--pink);}
  .time-slot.selected{background:var(--pink);border-color:var(--pink);color:#fff;}
  .tz-note{font-size:12px;color:var(--text-muted);margin-top:14px;}

  .booking-summary{display:flex;align-items:center;gap:12px;background:var(--pink-soft);border:1px solid var(--pink-border);border-radius:14px;padding:14px 16px;margin-top:16px;}

  .modal-footer{display:flex;align-items:center;justify-content:space-between;margin-top:22px;padding-top:18px;border-top:1px solid var(--border);}
  .btn-pink:disabled{opacity:.4;pointer-events:none;}

  @media (max-width: 900px){
    #desktop-nav-links, #desktop-actions { display: none !important; }
    #mobile-menu-btn { display: inline-flex !important; }
  }
  @media (min-width: 901px){
    #mobile-menu-btn, #mobile-menu { display: none !important; }
  }
  #mobile-menu a{border-bottom:1px solid var(--border);}
  #mobile-menu a:last-of-type{border-bottom:none;}

  @media (prefers-reduced-motion: reduce){ .marquee-track{animation:none;} }

  /* ABAT Training Program promo popup — shared across every landing page */
  .promo-popup {
    position: fixed; right: 18px; bottom: 18px; z-index: 55; width: min(280px, calc(100vw - 36px));
    animation: promo-in .5s cubic-bezier(.22,.61,.36,1) .3s both;
  }
  @keyframes promo-in { from { opacity: 0; transform: translateY(16px) scale(.96); } to { opacity: 1; transform: none; } }

  .promo-tab {
    display: flex; align-items: center; gap: 8px; margin-left: auto; border: none; cursor: pointer;
    background: var(--navy-deep); color: #fff; font: 700 12px 'Nunito Sans'; white-space: nowrap;
    padding: 10px 16px; border-radius: 999px; box-shadow: 0 6px 20px rgba(22,42,60,.3);
    transition: background .15s ease;
  }
  .promo-tab:hover { background: var(--pink); }
  .promo-tab-chevron { transition: transform .25s ease; flex-shrink: 0; }
  .promo-popup.open .promo-tab-chevron { transform: rotate(180deg); }

  .promo-panel {
    overflow: hidden; max-height: 0; opacity: 0; margin-top: 0;
    transition: max-height .35s cubic-bezier(.4,0,.2,1), opacity .25s ease, margin-top .35s cubic-bezier(.4,0,.2,1);
  }
  .promo-popup.open .promo-panel { max-height: 600px; opacity: 1; margin-top: 10px; }

  .promo-image-btn {
    display: block; width: 100%; padding: 0; border: none; background: none; cursor: pointer;
    border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(22,42,60,.28); line-height: 0;
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .promo-image-btn:hover { transform: translateY(-2px); box-shadow: 0 14px 36px rgba(22,42,60,.34); }
  .promo-image-btn img { width: 100%; height: auto; display: block; }

  .promo-lightbox {
    display: none; position: fixed; inset: 0; z-index: 9998; background: rgba(14,46,76,.82);
    align-items: center; justify-content: center; padding: 24px;
  }
  .promo-lightbox.open { display: flex; }
  .promo-lightbox img { max-width: min(560px, 100%); max-height: 88vh; width: auto; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
  .promo-lightbox-close {
    position: fixed; top: 22px; right: 22px; width: 40px; height: 40px; border-radius: 50%;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.3); color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
  }
  .promo-lightbox-close:hover { background: rgba(255,255,255,.22); }

  @media (max-width: 640px) {
    .promo-popup { right: 12px; bottom: 12px; width: min(200px, calc(100vw - 24px)); }
  }
</style>
@stack('head')
</head>
<body class="antialiased">

@php
  $navItems = [
    'home' => ['label' => 'Home', 'href' => url('/')],
    'services' => ['label' => 'Our Services', 'href' => route('services')],
    'about' => ['label' => 'About Us', 'href' => route('about-us')],
    'careers' => ['label' => 'Careers', 'href' => route('careers')],
    'blog' => ['label' => 'Blog', 'href' => route('blog')],
    'contact' => ['label' => 'Contact', 'href' => route('contact')],
  ];
@endphp

<!-- Navigation -->
<header class="sticky top-0 z-50 bg-white border-b border-[var(--border)]">
  <div class="max-w-[1440px] mx-auto px-6 lg:px-8 h-20 flex items-center justify-between gap-6">
    <a href="/" class="flex items-center shrink-0">
      <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic logo" class="h-14 w-auto object-contain">
    </a>

    <div id="desktop-nav-links" class="hidden md:flex items-center gap-7">
      @foreach ($navItems as $key => $item)
        <a href="{{ $item['href'] }}" class="nav-link {{ $activePage === $key ? 'active' : '' }}">{{ $item['label'] }}</a>
      @endforeach
    </div>

    <div id="desktop-actions" class="hidden md:flex items-center gap-4 shrink-0">
      <a href="tel:+971508846801" class="flex items-center gap-2 text-sm font-semibold text-[var(--navy)]">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        +971 50 884 6801
      </a>
      <a href="#" onclick="event.preventDefault(); openBookingModal();" class="btn-pink !py-2.5 !px-5 !text-sm">Free Consultation</a>
      <a href="https://wa.me/971508846801" target="_blank" rel="noopener" class="btn-whatsapp">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A8.86 8.86 0 0 0 12.05 4c-4.87 0-8.82 3.93-8.83 8.77 0 1.55.4 3.06 1.17 4.4L3.2 21.5l4.46-1.16a8.9 8.9 0 0 0 4.38 1.11h.01c4.87 0 8.82-3.93 8.83-8.77a8.65 8.65 0 0 0-2.68-6.36zM12.05 20a7.4 7.4 0 0 1-3.77-1.03l-.27-.16-2.8.73.75-2.7-.18-.28a7.33 7.33 0 0 1-1.14-3.9c0-4.05 3.32-7.35 7.42-7.35a7.4 7.4 0 0 1 5.24 2.16 7.24 7.24 0 0 1 2.17 5.19c0 4.05-3.32 7.34-7.42 7.34zm4.06-5.5c-.22-.11-1.32-.65-1.52-.72-.2-.08-.35-.11-.5.11-.15.22-.58.72-.71.87-.13.15-.26.16-.48.05-.22-.11-.93-.34-1.78-1.09a6.7 6.7 0 0 1-1.23-1.53c-.13-.22-.01-.34.1-.45.1-.1.22-.26.33-.4.11-.13.15-.22.22-.37.07-.15.04-.28-.02-.4-.06-.11-.5-1.2-.68-1.65-.18-.43-.36-.37-.5-.38h-.43c-.15 0-.4.06-.6.28-.2.22-.8.78-.8 1.9s.82 2.2.94 2.35c.11.15 1.61 2.46 3.9 3.45.55.24.97.38 1.3.48.55.17 1.04.15 1.44.09.44-.07 1.32-.54 1.5-1.06.19-.52.19-.97.13-1.06-.05-.1-.2-.16-.42-.27z"/></svg>
        WhatsApp Us
      </a>
    </div>

    <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg border border-[var(--border)] bg-white">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--navy)" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>

  <div id="mobile-menu" class="md:hidden hidden flex flex-col bg-white border-t border-[var(--border)] px-6 py-2">
    @foreach ($navItems as $key => $item)
      <a href="{{ $item['href'] }}" class="nav-link px-2 py-3 {{ $activePage === $key ? 'active' : '' }}">{{ $item['label'] }}</a>
    @endforeach
    <div class="flex items-center gap-3 py-3">
      <a href="#" onclick="event.preventDefault(); openBookingModal();" class="btn-pink !text-sm flex-1 justify-center">Free Consultation</a>
      <a href="https://wa.me/971508846801" target="_blank" rel="noopener" class="btn-whatsapp">WhatsApp</a>
    </div>
  </div>
</header>

@yield('content')

<!-- Footer -->
<footer class="footer-dark pt-16 pb-8">
  <div class="max-w-[1440px] mx-auto px-6 lg:px-8 grid grid-cols-1 md:grid-cols-[1.3fr_1fr_1fr] gap-10">
    <div>
      <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic logo" class="h-20 w-auto object-contain" style="filter:brightness(0) invert(1);">
      <p class="text-sm mt-4 max-w-xs" style="color:rgba(255,255,255,.65);">Empowering children and families to thrive every day through flexible, evidence-based ABA therapy across Abu Dhabi, UAE.</p>
      <div class="flex items-center gap-2.5 mt-5">
        <a href="https://www.instagram.com/engageclinicuae?igsi=MXhsdXNvemJ1a3pxZQ%3D%3D&amp;utm_source=qr" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Instagram"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg></a>
        <a href="https://www.linkedin.com/company/engageclinic/" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="LinkedIn"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4V8h4v1.5A6 6 0 0 1 16 8z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
        <a href="https://www.facebook.com/engageBL/" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Facebook"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
        <a href="https://www.tiktok.com/@engageclinicuae?_r=1&amp;_t=ZS-99Reo1xrcCW" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="TikTok"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg></a>
      </div>
    </div>

    <div>
      <span class="footer-heading">Pages</span>
      <ul class="space-y-2.5">
        <li><a href="{{ url('/') }}" class="footer-link">Home</a></li>
        <li><a href="{{ route('services') }}" class="footer-link">Our Services</a></li>
        <li><a href="{{ route('about-us') }}" class="footer-link">About Us</a></li>
        <li><a href="{{ route('careers') }}" class="footer-link">Careers</a></li>
        <li><a href="{{ route('blog') }}" class="footer-link">Blog</a></li>
        <li><a href="{{ route('contact') }}" class="footer-link">Contact</a></li>
      </ul>
    </div>

    <div>
      <span class="footer-heading">Contact</span>
      <ul class="space-y-3 text-sm" style="color:rgba(255,255,255,.72);">
        <li class="flex items-center gap-2.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--pink-mid)" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> +971 50 884 6801</li>
        <li class="flex items-center gap-2.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--pink-mid)" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg> info@engagebehavior.com</li>
        <li class="flex items-start gap-2.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--pink-mid)" stroke-width="2" class="mt-0.5 shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg> Office no. 1203, ADCP Commercial Tower-C, Electra Street, Abu Dhabi, UAE</li>
      </ul>
    </div>
  </div>

  <div class="max-w-[1440px] mx-auto px-6 lg:px-8 mt-12 pt-6 flex flex-col md:flex-row justify-between gap-2 text-xs" style="border-top:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.5);">
    <span>© 2026 Engage Behavioral Development Clinic LLC. All rights reserved.</span>
    <span>Designed &amp; Developed by <a href="#" class="underline hover:text-white">CRWD Dubai</a></span>
  </div>
</footer>

<!-- ABAT Training Program promo popup -->
<div class="promo-popup open" id="promoPopup">
  <button type="button" class="promo-tab" id="promoToggle">
    <span>🎓 ABAT Training Program</span>
    <svg class="promo-tab-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
  </button>
  <div class="promo-panel">
    <button type="button" class="promo-image-btn" id="promoOpen" aria-label="View ABAT Training Program flyer">
      <img src="{{ asset('uploads/ABAT.png') }}" alt="ABAT Training Program — Applied Behavior Analysis Technician, Engage Clinic">
    </button>
  </div>
</div>

<!-- Lightbox for the full-size flyer -->
<div class="promo-lightbox" id="promoLightbox">
  <button type="button" class="promo-lightbox-close" id="promoLightboxClose" aria-label="Close">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6 6 18M6 6l12 12"/></svg>
  </button>
  <img src="{{ asset('uploads/ABAT.png') }}" alt="ABAT Training Program — Applied Behavior Analysis Technician, Engage Clinic">
</div>

<script>
  // ABAT Training Program promo popup — shared across every page
  (function () {
    var popup = document.getElementById('promoPopup');
    var toggleBtn = document.getElementById('promoToggle');
    var openBtn = document.getElementById('promoOpen');
    var lightbox = document.getElementById('promoLightbox');
    var lightboxClose = document.getElementById('promoLightboxClose');
    if (!popup) return;

    // Remembers collapsed/expanded across page loads within this browser
    // session, rather than always resetting to open.
    if (sessionStorage.getItem('abatPromoCollapsed') === '1') {
      popup.classList.remove('open');
    }

    toggleBtn.addEventListener('click', function () {
      var isOpen = popup.classList.toggle('open');
      sessionStorage.setItem('abatPromoCollapsed', isOpen ? '0' : '1');
    });

    function openLightbox() { lightbox.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeLightbox() { lightbox.classList.remove('open'); document.body.style.overflow = ''; }

    openBtn.addEventListener('click', openLightbox);
    lightboxClose.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (e) { if (e.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && lightbox.classList.contains('open')) closeLightbox(); });
  })();
</script>

<script>
  // Mobile menu toggle — shared across every page
  const menuBtn = document.getElementById('mobile-menu-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  if (menuBtn && mobileMenu) {
    menuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
    document.querySelectorAll('#mobile-menu a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.add('hidden')));
  }
</script>

<script>
  // GSAP hero text reveal — plays once on load for whichever page's hero (marked
  // with .js-hero) is present. Every landing page shares the same eyebrow/h1/p/CTA
  // shape, so this one script drives all of them. On the homepage the hero copy is
  // also swapped by the gallery carousel (see welcome.blade.php) via its own CSS
  // transitions - this only owns the very first entrance, then clears its inline
  // styles so that carousel is free to take over afterwards.
  (function () {
    if (typeof gsap === 'undefined') return;

    const hero = document.querySelector('.js-hero');
    if (!hero) return;

    const eyebrow = hero.querySelector('.eyebrow, .eyebrow-plain');
    const heading = hero.querySelector('h1');
    const paragraph = hero.querySelector('p');
    const ctas = hero.querySelectorAll('.btn-pink, .btn-ghost, .btn-outline-white, .btn-white');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let words = [];
    if (heading) {
      heading.innerHTML = heading.textContent
        .trim()
        .split(/\s+/)
        .map(word => `<span style="display:inline-block;overflow:hidden;vertical-align:top;padding-bottom:0.15em;margin-bottom:-0.15em;"><span style="display:inline-block;">${word}</span></span>`)
        .join(' ');
      words = Array.from(heading.querySelectorAll(':scope > span > span'));
    }

    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    if (eyebrow) tl.from(eyebrow, { opacity: 0, y: 14, duration: 0.5 });
    if (words.length) tl.from(words, { yPercent: 110, opacity: 0, duration: 0.7, stagger: 0.045 }, eyebrow ? '-=0.25' : 0);
    if (paragraph) tl.from(paragraph, { opacity: 0, y: 14, duration: 0.5 }, '-=0.35');
    if (ctas.length) tl.from(ctas, { opacity: 0, y: 14, duration: 0.5, stagger: 0.08 }, '-=0.3');

    tl.eventCallback('onComplete', function () {
      gsap.set([eyebrow, heading, paragraph, ...ctas, ...words].filter(Boolean), { clearProps: 'all' });
    });
  })();
</script>

<script>
  // GSAP ScrollTrigger text reveal — shared across every page. Sections opt in
  // per element with one of three classes:
  //   .reveal-heading  headings, split into words and revealed with a stagger
  //   .reveal-text     paragraphs/labels, simple fade + upward move
  //   .reveal-group    a grid/row container whose direct children fade + move
  //                    up together with a stagger, as one entrance
  // Each element replays every time it scrolls into view — forward when
  // entering from below, reset back to hidden when scrolled back above it —
  // so the reveal repeats on every pass, not just the first. The hero is
  // excluded since it already animates on load.
  (function () {
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    gsap.registerPlugin(ScrollTrigger);

    document.querySelectorAll('.reveal-heading').forEach(function (heading) {
      if (heading.closest('.js-hero')) return;

      // Wrap each word in an overflow-hidden mask span so it can slide up into
      // view. Walks child nodes rather than using textContent so that nested
      // markup (e.g. a highlighted <span style="color:...">) keeps its own
      // styling instead of being flattened to plain text.
      const originalNodes = Array.from(heading.childNodes);
      heading.innerHTML = '';
      originalNodes.forEach(function (node) {
        if (node.nodeType === Node.TEXT_NODE) {
          const parts = node.textContent.split(/(\s+)/);
          parts.forEach(function (part) {
            if (part.trim() === '') {
              heading.appendChild(document.createTextNode(part));
              return;
            }
            const outer = document.createElement('span');
            outer.style.cssText = 'display:inline-block;overflow:hidden;vertical-align:top;padding-bottom:0.15em;margin-bottom:-0.15em;';
            const inner = document.createElement('span');
            inner.style.display = 'inline-block';
            inner.textContent = part;
            outer.appendChild(inner);
            heading.appendChild(outer);
          });
        } else {
          const outer = document.createElement('span');
          outer.style.cssText = 'display:inline-block;overflow:hidden;vertical-align:top;padding-bottom:0.15em;margin-bottom:-0.15em;';
          const inner = document.createElement('span');
          inner.style.display = 'inline-block';
          inner.appendChild(node);
          outer.appendChild(inner);
          heading.appendChild(outer);
        }
      });
      const words = heading.querySelectorAll(':scope > span > span');

      gsap.from(words, {
        yPercent: 110,
        opacity: 0,
        duration: 0.7,
        stagger: 0.035,
        ease: 'power3.out',
        scrollTrigger: { trigger: heading, start: 'top 85%', toggleActions: 'restart none none reverse' },
      });
    });

    gsap.utils.toArray('.reveal-text').forEach(function (el) {
      gsap.from(el, {
        y: 22,
        opacity: 0,
        duration: 0.65,
        ease: 'power3.out',
        scrollTrigger: { trigger: el, start: 'top 88%', toggleActions: 'restart none none reverse' },
      });
    });

    gsap.utils.toArray('.reveal-group').forEach(function (group) {
      const items = group.children;
      if (!items.length) return;
      gsap.from(items, {
        y: 24,
        opacity: 0,
        duration: 0.6,
        stagger: 0.08,
        ease: 'power3.out',
        scrollTrigger: { trigger: group, start: 'top 85%', toggleActions: 'restart none none reverse' },
      });
    });
  })();
</script>

@stack('scripts')
</body>
</html>

<?php
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>gloMenu — Restoranlar üçün QR menyu</title>
<meta name="description" content="gloMenu — restoranlar üçün QR menyu platforması. 10 dəqiqəyə quraşdırın, menyunu telefondan idarə edin.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0C0A09; --surface:#141210; --surface2:#1B1815;
  --line:rgba(245,240,232,.09); --line2:rgba(245,240,232,.14);
  --ink:#F5F0E8; --muted:#A8A29A; --faint:#6E665C;
  --red:#E5484D; --red-deep:#B91C1C;
  --paper-btn:#F5F0E8;
  --r:16px; --font-d:'Space Grotesk',Inter,sans-serif; --font-b:Inter,-apple-system,sans-serif;
}
html{scroll-behavior:smooth}
body{font-family:var(--font-b);background:var(--bg);color:var(--ink);line-height:1.6;font-size:15.5px;-webkit-font-smoothing:antialiased;overflow-x:hidden}
::selection{background:var(--red);color:#fff}
.wrap{max-width:1140px;margin:0 auto;padding:0 24px}
a{color:inherit}
/* ---------- nav ---------- */
.nav{position:sticky;top:0;z-index:50;background:rgba(12,10,9,.78);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid transparent;transition:border-color .25s}
.nav.scrolled{border-bottom-color:var(--line)}
.nav-in{display:flex;align-items:center;justify-content:space-between;height:66px}
.brand{display:flex;align-items:center;gap:10px;font-family:var(--font-d);font-weight:700;font-size:21px;text-decoration:none;letter-spacing:-.02em}
.brand-mark{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--red),var(--red-deep));display:flex;align-items:center;justify-content:center;font-size:19px;color:#fff;font-weight:700}
.nav-links{display:flex;align-items:center;gap:28px}
.nav-links a{text-decoration:none;color:var(--muted);font-size:14.5px;font-weight:500;transition:color .15s}
.nav-links a:hover{color:var(--ink)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 22px;border-radius:11px;font-weight:600;font-size:14.5px;text-decoration:none;border:1px solid transparent;cursor:pointer;transition:transform .15s,background .15s,border-color .15s;font-family:var(--font-b)}
.btn:active{transform:scale(.97)}
.btn-light{background:var(--paper-btn);color:#141210}
.btn-light:hover{background:#fff}
.btn-ghost{border-color:var(--line2);color:var(--ink);background:transparent}
.btn-ghost:hover{border-color:var(--muted)}
.btn-red{background:var(--red-deep);color:#fff}
.btn-red:hover{background:var(--red)}
.btn-sm{padding:9px 16px;font-size:13.5px;border-radius:9px}
.burger{display:none;background:none;border:1px solid var(--line2);border-radius:9px;width:40px;height:40px;color:var(--ink);cursor:pointer}
/* ---------- hero ---------- */
.hero{position:relative;padding:88px 0 72px;overflow:hidden}
.hero::before{content:"";position:absolute;top:-320px;left:50%;transform:translateX(-50%);width:900px;height:640px;background:radial-gradient(ellipse at center,rgba(229,72,77,.13),transparent 65%);pointer-events:none}
.hero::after{content:"";position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.05'/%3E%3C/svg%3E");pointer-events:none}
.hero-grid{position:relative;display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center;z-index:1}
.eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);border:1px solid var(--line2);border-radius:99px;padding:7px 14px;margin-bottom:22px}
.eyebrow i{width:7px;height:7px;border-radius:50%;background:var(--red);box-shadow:0 0 10px var(--red)}
.hero h1{font-family:var(--font-d);font-size:clamp(2.6rem,5.2vw,4rem);line-height:1.04;letter-spacing:-.035em;font-weight:700;margin-bottom:20px}
.hero h1 em{font-style:normal;color:var(--red)}
.hero-sub{font-size:17px;color:var(--muted);max-width:480px;margin-bottom:30px}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.hero-note{font-size:13px;color:var(--faint)}
.hero-note b{color:var(--muted);font-weight:600}
/* phone */
.phone-zone{position:relative;display:flex;justify-content:center}
.phone{width:288px;border-radius:44px;background:#000;border:1px solid var(--line2);padding:11px;box-shadow:0 40px 90px rgba(0,0,0,.6),0 0 0 8px rgba(245,240,232,.03);position:relative}
.phone-screen{background:#171412;border-radius:34px;overflow:hidden;padding:14px 13px 16px}
.notch{width:96px;height:24px;background:#000;border-radius:99px;margin:0 auto 12px}
.m-name{font-family:var(--font-d);font-weight:700;font-size:16.5px;letter-spacing:-.01em;margin:2px 0 10px}
.m-search{display:flex;align-items:center;gap:7px;background:#221E1A;border:1px solid var(--line);border-radius:9px;padding:7px 10px;font-size:11.5px;color:var(--faint);margin-bottom:10px}
.m-pills{display:flex;gap:6px;margin-bottom:11px}
.m-pill{font-size:10.5px;font-weight:600;padding:5px 10px;border-radius:99px;background:#221E1A;color:var(--muted);border:1px solid transparent}
.m-pill.on{background:var(--red-deep);color:#fff}
.m-dish{display:flex;gap:10px;background:#1E1A16;border:1px solid var(--line);border-radius:13px;padding:9px;margin-bottom:9px;align-items:center}
.m-cover{width:52px;height:52px;border-radius:10px;flex-shrink:0}
.m-c1{background:linear-gradient(135deg,#7C2D12,#C2410C)}
.m-c2{background:linear-gradient(135deg,#14532D,#16A34A)}
.m-c3{background:linear-gradient(135deg,#713F12,#D97706)}
.m-dish b{display:block;font-size:12px;font-weight:600}
.m-dish small{display:block;font-size:10.5px;color:var(--faint)}
.m-price{margin-left:auto;font-size:12.5px;font-weight:700;color:var(--red);white-space:nowrap}
.m-rate{display:flex;align-items:center;gap:6px;background:#1E1A16;border:1px solid var(--line);border-radius:13px;padding:9px 11px;font-size:11px;color:var(--muted)}
.m-rate .stars{color:#F59E0B;letter-spacing:1px}
.float-card{position:absolute;background:rgba(27,24,21,.92);border:1px solid var(--line2);border-radius:14px;padding:11px 14px;font-size:12px;backdrop-filter:blur(8px);box-shadow:0 18px 44px rgba(0,0,0,.5);display:flex;align-items:center;gap:10px;z-index:2}
.float-card b{display:block;font-size:13px}
.float-card small{color:var(--muted);font-size:11px}
.fc-qr{top:52px;left:-8px}
.fc-rate{bottom:56px;right:-6px}
.fc-ic{width:34px;height:34px;border-radius:10px;background:rgba(229,72,77,.14);display:flex;align-items:center;justify-content:center;flex-shrink:0}
/* ---------- facts ---------- */
.facts{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:rgba(255,255,255,.012)}
.facts-in{display:grid;grid-template-columns:repeat(4,1fr);padding:26px 0}
.fact{text-align:center;padding:6px 12px}
.fact + .fact{border-left:1px solid var(--line)}
.fact b{font-family:var(--font-d);font-size:27px;letter-spacing:-.02em;display:block}
.fact b em{font-style:normal;color:var(--red)}
.fact span{font-size:13px;color:var(--muted)}
/* ---------- marquee ---------- */
.marquee{overflow:hidden;border-bottom:1px solid var(--line);padding:15px 0;white-space:nowrap}
.marquee-track{display:inline-flex;gap:44px;animation:mq 28s linear infinite;font-family:var(--font-d);font-size:13.5px;letter-spacing:.16em;text-transform:uppercase;color:var(--faint)}
.marquee-track span{display:flex;align-items:center;gap:44px}
.marquee-track i{width:5px;height:5px;border-radius:50%;background:var(--red-deep);flex-shrink:0}
@keyframes mq{to{transform:translateX(-50%)}}
/* ---------- sections ---------- */
section.block{padding:88px 0}
.sec-head{max-width:620px;margin-bottom:44px}
.sec-head.center{margin-left:auto;margin-right:auto;text-align:center}
.kicker{font-size:12.5px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--red);margin-bottom:12px}
.sec-head h2{font-family:var(--font-d);font-size:clamp(1.7rem,3.4vw,2.5rem);letter-spacing:-.03em;line-height:1.12;margin-bottom:12px}
.sec-head p{color:var(--muted);font-size:16px}
/* steps */
.steps{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;counter-reset:step}
.step{background:var(--surface);border:1px solid var(--line);border-radius:var(--r);padding:30px 26px;position:relative;overflow:hidden}
.step::after{content:"0" counter(step);counter-increment:step;position:absolute;top:14px;right:20px;font-family:var(--font-d);font-size:44px;font-weight:700;color:rgba(245,240,232,.07);line-height:1}
.step h3{font-size:16.5px;font-weight:600;margin:16px 0 8px}
.step p{font-size:14px;color:var(--muted)}
.step-ic{width:44px;height:44px;border-radius:12px;background:rgba(229,72,77,.12);display:flex;align-items:center;justify-content:center}
/* bento */
.bento{display:grid;grid-template-columns:1.2fr 1fr 1fr;grid-template-rows:auto auto;gap:18px}
.b-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--r);padding:30px 28px;position:relative;overflow:hidden;transition:border-color .2s}
.b-card:hover{border-color:var(--line2)}
.b-card h3{font-size:17px;font-weight:600;margin:16px 0 8px}
.b-card p{font-size:14px;color:var(--muted)}
.b-wide{grid-column:1/3}
.b-tall{grid-row:span 2}
.b-ic{width:42px;height:42px;border-radius:11px;background:#221E1A;border:1px solid var(--line);display:flex;align-items:center;justify-content:center}
.mini-bars{margin-top:20px;display:flex;flex-direction:column;gap:9px}
.mini-bar{display:flex;align-items:center;gap:10px;font-size:12px;color:var(--muted)}
.mini-bar .tr{flex:1;height:7px;border-radius:99px;background:#26211C;overflow:hidden}
.mini-bar .fl{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--red-deep),var(--red))}
.lang-row{display:flex;gap:8px;margin-top:20px;flex-wrap:wrap}
.lang-chip{font-size:12.5px;font-weight:600;border:1px solid var(--line2);border-radius:99px;padding:7px 14px;color:var(--muted)}
.lang-chip.on{background:var(--paper-btn);color:#141210;border-color:var(--paper-btn)}
.theme-dots{display:flex;gap:9px;margin-top:20px}
.theme-dots i{width:26px;height:26px;border-radius:50%;border:2px solid rgba(255,255,255,.15)}
.check-list{list-style:none;margin-top:18px;display:flex;flex-direction:column;gap:11px}
.check-list li{display:flex;gap:10px;align-items:flex-start;font-size:14px;color:var(--muted)}
.check-list b{color:var(--ink);font-weight:600}
.tick{width:20px;height:20px;border-radius:50%;background:rgba(22,163,74,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
/* ---------- pricing ---------- */
.pricing{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;align-items:stretch}
.price-card{background:var(--surface);border:1px solid var(--line);border-radius:18px;padding:32px 30px;display:flex;flex-direction:column;position:relative}
.price-card.hot{border-color:rgba(229,72,77,.55);background:linear-gradient(180deg,rgba(229,72,77,.07),var(--surface) 55%)}
.price-flag{position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--red);color:#fff;font-size:11.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:99px;white-space:nowrap}
.price-card h3{font-size:15px;font-weight:600;color:var(--muted)}
.price-num{font-family:var(--font-d);font-size:44px;font-weight:700;letter-spacing:-.03em;margin:10px 0 2px}
.price-num small{font-size:14px;font-family:var(--font-b);font-weight:400;color:var(--faint)}
.price-per{font-size:13px;color:var(--faint);margin-bottom:20px}
.price-card ul{list-style:none;margin-bottom:26px;display:flex;flex-direction:column;gap:10px;flex:1}
.price-card li{display:flex;gap:10px;align-items:flex-start;font-size:14px;color:var(--muted)}
.price-note{text-align:center;color:var(--faint);font-size:13px;margin-top:22px}
/* ---------- faq ---------- */
.faq{max-width:720px;margin:0 auto;display:flex;flex-direction:column;gap:10px}
.faq details{background:var(--surface);border:1px solid var(--line);border-radius:13px;overflow:hidden}
.faq details[open]{border-color:var(--line2)}
.faq summary{cursor:pointer;list-style:none;padding:18px 22px;font-weight:600;font-size:15px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.faq summary::-webkit-details-marker{display:none}
.faq summary .pm{font-family:var(--font-d);color:var(--red);font-size:19px;flex-shrink:0}
.faq details p{padding:0 22px 20px;color:var(--muted);font-size:14.5px}
/* ---------- cta ---------- */
.cta-panel{background:linear-gradient(135deg,#7F1D1D,#B91C1C 55%,#7F1D1D);border-radius:24px;padding:64px 32px;text-align:center;position:relative;overflow:hidden}
.cta-panel::before{content:"";position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.09'/%3E%3C/svg%3E")}
.cta-panel h2{font-family:var(--font-d);font-size:clamp(1.8rem,3.6vw,2.7rem);letter-spacing:-.03em;position:relative;margin-bottom:12px}
.cta-panel p{color:rgba(255,255,255,.75);position:relative;margin-bottom:28px;font-size:16px}
.btn-white{background:#fff;color:#141210;position:relative}
.btn-white:hover{background:var(--paper-btn)}
/* ---------- footer ---------- */
footer{border-top:1px solid var(--line);padding:52px 0 28px;margin-top:88px}
.foot-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:32px;margin-bottom:40px}
.foot-grid p{font-size:13.5px;color:var(--muted);margin-top:12px;max-width:260px}
.foot-grid h4{font-size:12.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:14px}
.foot-grid a{display:block;font-size:14px;color:var(--muted);text-decoration:none;margin-bottom:9px}
.foot-grid a:hover{color:var(--ink)}
.foot-bottom{border-top:1px solid var(--line);padding-top:22px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:13px;color:var(--faint)}
/* reveal */
.rv{opacity:0;transform:translateY(22px);transition:opacity .7s ease,transform .7s ease}
.rv.in{opacity:1;transform:none}
@media(prefers-reduced-motion:reduce){.rv{opacity:1;transform:none}.marquee-track{animation:none}html{scroll-behavior:auto}}
/* ---------- responsive ---------- */
@media(max-width:960px){
  .hero-grid{grid-template-columns:1fr;gap:48px}
  .hero{padding:64px 0 56px}
  .phone-zone{order:2}
  .fc-qr{left:2px}.fc-rate{right:2px}
  .bento{grid-template-columns:1fr 1fr}
  .b-wide{grid-column:1/-1}.b-tall{grid-row:auto}
  .pricing{grid-template-columns:1fr;max-width:440px;margin:0 auto}
  .steps{grid-template-columns:1fr}
  .facts-in{grid-template-columns:1fr 1fr;gap:18px}
  .fact + .fact{border-left:none}
  .foot-grid{grid-template-columns:1fr 1fr}
  .nav-links{display:none;position:absolute;top:66px;left:0;right:0;background:#141210;border-bottom:1px solid var(--line);flex-direction:column;align-items:stretch;padding:12px 24px 20px;gap:4px}
  .nav-links.open{display:flex}
  .nav-links a{padding:10px 0;border-bottom:1px solid var(--line)}
  .nav-links .btn{margin-top:10px}
  .burger{display:block}
  section.block{padding:64px 0}
}
@media(max-width:560px){.bento{grid-template-columns:1fr}.facts-in{grid-template-columns:1fr 1fr}.foot-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<header class="nav" id="nav">
  <div class="wrap nav-in">
    <a class="brand" href="#top" aria-label="gloMenu ana səhifə"><span class="brand-mark">g</span>gloMenu<span style="color:var(--red)">.</span></a>
    <nav class="nav-links" id="navLinks" aria-label="Əsas naviqasiya">
      <a href="#nece">Necə işləyir</a>
      <a href="#ozellik">Özəlliklər</a>
      <a href="#qiymet">Qiymətlər</a>
      <a href="#faq">Suallar</a>
      <a class="btn btn-light btn-sm" href="#cta">Pulsuz başla</a>
    </nav>
    <button class="burger" id="burger" aria-label="Menyunu aç" aria-expanded="false">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
  </div>
</header>

<main id="top">
<!-- HERO -->
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow"><i></i>QR menyu platforması</span>
      <h1>Menyunuz cibdə,<br>qonaq <em>masada.</em></h1>
      <p class="hero-sub">gloMenu kağız menyunu əvəz edir: qonaq QR-i skan edir, yeməyi seçir, ofisiantı çağırır. Siz isə hər şeyi telefondan idarə edirsiniz.</p>
      <div class="hero-cta">
        <a class="btn btn-light" href="#cta">Pulsuz başla
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
        <a class="btn btn-ghost" href="#nece">Necə işləyir</a>
      </div>
      <p class="hero-note"><b>10 dəqiqəyə</b> quraşdırma &nbsp;·&nbsp; kredit kartı lazım deyil</p>
    </div>
    <div class="phone-zone">
      <div class="float-card fc-qr">
        <span class="fc-ic">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><path d="M14 14h3v3h-3zM21 14v.01M14 21v.01M18 18h3v3h-3z"/></svg>
        </span>
        <span><b>Masa 7 skan edildi</b><small>menyu açıldı · 0.8 saniyə</small></span>
      </div>
      <div class="phone" aria-hidden="true">
        <div class="phone-screen">
          <div class="notch"></div>
          <p class="m-name">Nümunə Restoran</p>
          <div class="m-search">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            Yemək axtar…
          </div>
          <div class="m-pills"><span class="m-pill on">Burger</span><span class="m-pill">Pizza</span><span class="m-pill">Salat</span></div>
          <div class="m-dish"><span class="m-cover m-c1"></span><span><b>Çizburger</b><small>250 q</small></span><span class="m-price">8,50 ₼</span></div>
          <div class="m-dish"><span class="m-cover m-c2"></span><span><b>Sezar salatı</b><small>180 q</small></span><span class="m-price">6,00 ₼</span></div>
          <div class="m-dish"><span class="m-cover m-c3"></span><span><b>Donat</b><small>90 q</small></span><span class="m-price">3,20 ₼</span></div>
          <div class="m-rate"><span class="stars">★★★★★</span> 4.9 · 2 300+ qonaq balı</div>
        </div>
      </div>
      <div class="float-card fc-rate">
        <span class="fc-ic">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        </span>
        <span><b>Ofisiant çağırıldı</b><small>masa 12 · indi</small></span>
      </div>
    </div>
  </div>
</section>

<!-- FACTS -->
<div class="facts">
  <div class="wrap facts-in">
    <div class="fact"><b><em>10</em> dəq</b><span>orta quraşdırma vaxtı</span></div>
    <div class="fact"><b><em>0.8</em> sn</b><span>menyu açılış sürəti</span></div>
    <div class="fact"><b><em>4</em> dil</b><span>AZ · EN · RU · TR</span></div>
    <div class="fact"><b><em>8</em> tema</b><span>restoranınıza uyğun</span></div>
  </div>
</div>

<!-- MARQUEE -->
<div class="marquee" aria-hidden="true">
  <div class="marquee-track" id="mqTrack">
    <span>QR menyu <i></i> Çoxdilli <i></i> Ofisiant çağırışı <i></i> Qonaq rəyləri <i></i> Canlı statistika <i></i> Wi-Fi paylaşımı <i></i>&nbsp;</span>
  </div>
</div>

<!-- HOW -->
<section class="block" id="nece">
  <div class="wrap">
    <div class="sec-head rv">
      <p class="kicker">Necə işləyir</p>
      <h2>Axşam servisinə qədər hazırsınız</h2>
      <p>Çap olunmuş QR-dən canlı menyuya üç addım.</p>
    </div>
    <div class="steps">
      <div class="step rv">
        <span class="step-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>
        <h3>Menyunu daxil edin</h3>
        <p>Kateqoriyalar, yeməklər, qiymətlər və şəkillər — hamısı sadə admin paneldən, telefondan da olar.</p>
      </div>
      <div class="step rv">
        <span class="step-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><path d="M14 14h3v3h-3zM21 14v.01M14 21v.01M18 18h3v3h-3z"/></svg></span>
        <h3>QR-i masaya qoyun</h3>
        <p>Hər masa üçün unikal QR yaradılır. Çap edin, stikerləyin — dəyişiklik olanda QR dəyişmir.</p>
      </div>
      <div class="step rv">
        <span class="step-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
        <h3>Qonaq istifadə edir</h3>
        <p>Skan → menyu → ofisiant çağırışı → ulduzlu rəy. Tətbiq yükləmək lazım deyil.</p>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES BENTO -->
<section class="block" id="ozellik" style="padding-top:0">
  <div class="wrap">
    <div class="sec-head rv">
      <p class="kicker">Özəlliklər</p>
      <h2>Menyudan çox — idarəetmə aləti</h2>
      <p>Qonağın gördüyü vitrin, sizin gördüyünüz rəqəmlər.</p>
    </div>
    <div class="bento">
      <div class="b-card b-wide rv">
        <span class="b-ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m7 14 4-4 4 3 5-6"/></svg></span>
        <h3>Hansı yemək qazandırır — bilin</h3>
        <p>Ziyarət sayı, ulduz ortalaması, ofisiant çağırışları. Hansı yeməyin söhbət doğurduğunu rəqəmlə görün.</p>
        <div class="mini-bars">
          <div class="mini-bar"><span style="width:92px">Çizburger</span><span class="tr"><span class="fl" style="display:block;width:86%"></span></span><span>4.9</span></div>
          <div class="mini-bar"><span style="width:92px">Pizza</span><span class="tr"><span class="fl" style="display:block;width:72%"></span></span><span>4.7</span></div>
          <div class="mini-bar"><span style="width:92px">Salat</span><span class="tr"><span class="fl" style="display:block;width:58%"></span></span><span>4.5</span></div>
        </div>
      </div>
      <div class="b-card rv">
        <span class="b-ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18"/></svg></span>
        <h3>4 dildə menyu</h3>
        <p>Azərbaycan, ingilis, rus, türk — turist qonaq da öz dilində oxuyur.</p>
        <div class="lang-row"><span class="lang-chip on">AZ</span><span class="lang-chip">EN</span><span class="lang-chip">RU</span><span class="lang-chip">TR</span></div>
      </div>
      <div class="b-card rv">
        <span class="b-ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h0a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h0a1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v0a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg></span>
        <h3>8 hazır tema</h3>
        <p>İnteryerinizə uyğun rəng seçin, ya öz rənglərinizi yazın.</p>
        <div class="theme-dots"><i style="background:#B91C1C"></i><i style="background:#1D4ED8"></i><i style="background:#7C3AED"></i><i style="background:#059669"></i><i style="background:#D9A419"></i></div>
      </div>
      <div class="b-card rv">
        <span class="b-ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg></span>
        <h3>Ofisiant çağırışı</h3>
        <p>Qonaq düyməni basır, mətbəx ekranında masa nömrəsi düşür.</p>
      </div>
      <div class="b-card b-wide rv">
        <span class="b-ic"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#E5484D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.5l1.2-6.5L2.5 9.4l6.6-.9z"/></svg></span>
        <h3>Rəy döngüsü bağlı qalır</h3>
        <p>Şikayət birbaşa sizə gəlir — sosial şəbəkəyə yox.</p>
        <ul class="check-list">
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span><b>Ulduzlu qiymət</b> — hər yeməyə ayrı bal</span></li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span><b>Səbəbli şikayət</b> — dad, porsiya, təmizlik</span></li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span><b>Təkrar səs qoruması</b> — eyni cihazdan bir rəy</span></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- PRICING -->
<section class="block" id="qiymet" style="padding-top:0">
  <div class="wrap">
    <div class="sec-head center rv">
      <p class="kicker">Qiymətlər</p>
      <h2>Bir axşamlıq qazancdan ucuz</h2>
      <p>İstədiyiniz vaxt dayandırın. Gizli ödəniş yoxdur.</p>
    </div>
    <div class="pricing">
      <div class="price-card rv">
        <h3>Başlanğıc</h3>
        <p class="price-num">0 <small>₼ / ay</small></p>
        <p class="price-per">Kiçik məkanlar üçün</p>
        <ul>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>50-yə qədər məhsul</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Standart temalar</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>QR kodlar daxil</li>
        </ul>
        <a class="btn btn-ghost" href="#cta" style="justify-content:center">Başla</a>
      </div>
      <div class="price-card hot rv">
        <span class="price-flag">Ən çox seçilən</span>
        <h3>Professional</h3>
        <p class="price-num">29 <small>₼ / ay</small></p>
        <p class="price-per">Əksər restoranlar üçün</p>
        <ul>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Limitsiz məhsul və kateqoriya</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Fərdi rənglər və loqo</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>4 dildə menyu</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Statistika və rəylər paneli</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Prioritet dəstək</li>
        </ul>
        <a class="btn btn-red" href="#cta" style="justify-content:center">Seç</a>
      </div>
      <div class="price-card rv">
        <h3>Şəbəkə</h3>
        <p class="price-num">59 <small>₼ / ay</small></p>
        <p class="price-per">Bir neçə filial üçün</p>
        <ul>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>5-ə qədər filial</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Mərkəzi idarəetmə</li>
          <li><span class="tick"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Fərdi domain</li>
        </ul>
        <a class="btn btn-ghost" href="#cta" style="justify-content:center">Danışaq</a>
      </div>
    </div>
    <p class="price-note">İlk 14 gün bütün planlarda pulsuz — kart məlumatı istənilmir.</p>
  </div>
</section>

<!-- FAQ -->
<section class="block" id="faq" style="padding-top:0">
  <div class="wrap">
    <div class="sec-head center rv">
      <p class="kicker">Suallar</p>
      <h2>Tez-tez soruşulanlar</h2>
    </div>
    <div class="faq">
      <details class="rv"><summary>QR kodları hardan çap etdirim?<span class="pm">+</span></summary><p>İstənilən çap mərkəzində — adi stiker kağızı kifayətdir. Ölçü tövsiyəmiz masaüstü stendlər üçün 6×6 sm-dir. Kodlar dəyişməzdir: menyunu yüz dəfə yeniləsəniz də təkrar çap lazım deyil.</p></details>
      <details class="rv"><summary>Qonaqda internet olmasa necə olacaq?<span class="pm">+</span></summary><p>Menyu restoranınızın Wi-Fi-ı ilə açılır — admin paneldə Wi-Fi şifrəsini bir dəfə yazırsınız, qonaq menyudan bir toxunuşla köçürür.</p></details>
      <details class="rv"><summary>Menyunu necə dəyişirəm?<span class="pm">+</span></summary><p>Admin paneldən — qiymət, şəkil, yeni yemək. Dəyişiklik dərhal canlı menyuda görünür, QR-a toxunmaq lazım deyil.</p></details>
      <details class="rv"><summary>Qonaq tətbiq yükləməlidir?<span class="pm">+</span></summary><p>Xeyr. Kamera ilə QR-i skan etmək kifayətdir — menyu brauzerdə açılır.</p></details>
      <details class="rv"><summary>Mövcud saytıma inteqrasiya olur?<span class="pm">+</span></summary><p>Bəli. Menyu ayrı linkdə yaşayır — saytınızdakı “Menyu” düyməsinə həmin linki qoyursunuz. İstəsəniz fərdi domain də qoşuruq.</p></details>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="block" id="cta" style="padding-top:0">
  <div class="wrap">
    <div class="cta-panel rv">
      <h2>Bu axşam kağızsız servisə başlayın</h2>
      <p>Menyunuzu yükləyin, QR-i çap edin, masalara qoyun. 10 dəqiqə vaxtınız gedər.</p>
      <a class="btn btn-white" href="mailto:info@glomenu.az?subject=gloMenu%20il%C9%99%20ba%C5%9Flamaq">Pulsuz başla
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
  </div>
</section>
</main>

<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <a class="brand" href="#top"><span class="brand-mark">g</span>gloMenu<span style="color:var(--red)">.</span></a>
        <p>Restoranlar üçün QR menyu platforması. Bakıda hazırlanır, hər yerdə işləyir.</p>
      </div>
      <div>
        <h4>Məhsul</h4>
        <a href="#nece">Necə işləyir</a>
        <a href="#ozellik">Özəlliklər</a>
        <a href="#qiymet">Qiymətlər</a>
      </div>
      <div>
        <h4>Kömək</h4>
        <a href="#faq">Suallar</a>
        <a href="mailto:info@glomenu.az">Dəstək</a>
      </div>
      <div>
        <h4>Əlaqə</h4>
        <a href="mailto:info@glomenu.az">info@glomenu.az</a>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© <?= $year ?> gloMenu. Bütün hüquqlar qorunur.</span>
      <span>Azərbaycanda hazırlanıb</span>
    </div>
  </div>
</footer>

<script>
(function(){
  "use strict";
  var nav=document.getElementById('nav'),burger=document.getElementById('burger'),links=document.getElementById('navLinks');
  function onScroll(){if(nav)nav.classList.toggle('scrolled',window.scrollY>8)}
  window.addEventListener('scroll',onScroll,{passive:true});onScroll();
  if(burger&&links){burger.addEventListener('click',function(){var o=links.classList.toggle('open');burger.setAttribute('aria-expanded',o?'true':'false')});
    links.addEventListener('click',function(e){if(e.target.closest('a')){links.classList.remove('open');burger.setAttribute('aria-expanded','false')}})}
  var mq=document.getElementById('mqTrack');
  if(mq){mq.innerHTML+=mq.innerHTML;}
  if('IntersectionObserver' in window){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.12,rootMargin:'0px 0px -40px 0px'});
    document.querySelectorAll('.rv').forEach(function(el){io.observe(el)});
  }else{document.querySelectorAll('.rv').forEach(function(el){el.classList.add('in')})}
  document.querySelectorAll('.faq summary').forEach(function(s){s.addEventListener('click',function(){var d=s.parentElement,was=d.open;setTimeout(function(){document.querySelectorAll('.faq details[open]').forEach(function(o){if(o!==d)o.open=false});s.querySelector('.pm').textContent=d.open?'\u2212':'+';document.querySelectorAll('.faq details').forEach(function(o){if(o!==d){var p=o.querySelector('.pm');if(p)p.textContent='+'}})},0)})});
})();
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KB Tours · Cusco, Perú</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />

  <style>
    /* ─── RESET ─── */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --gold:    #C9A84C;
      --gold-lt: #E8D5A3;
      --night:   #0B0F1A;
      --ink:     #1A1E2E;
      --white:   #FAFAF8;
      --glass:   rgba(10, 14, 26, 0.55);
      --radius:  4px;
      --ease:    cubic-bezier(.25,.46,.45,.94);
    }

    html, body {
       width: 100%;
       height: 100%;
       overflow: hidden;
       }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--night);
      color: var(--white);
    }

    /* ─── SLIDER ─── */
    .swiper {
      width: 100%;
      height: 100vh;
      position: relative;
    }

    .swiper-slide {
      position: relative;
      overflow: hidden;
    }

    .swiper-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: scale(1.06);
      transition: transform 6s var(--ease);
      will-change: transform;
    }

    .swiper-slide-active img {
      transform: scale(1);
    }

    /* gradient overlay — deep at bottom, hint at top */
    .swiper-slide::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(to top,  rgba(10,14,26,.92) 0%, rgba(10,14,26,.45) 45%, rgba(10,14,26,.20) 100%),
        linear-gradient(to right, rgba(10,14,26,.35) 0%, transparent 60%);
      z-index: 1;
    }

    /* ─── HEADER BAR ─── */
    .site-header {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 24px 48px;
      background: linear-gradient(to bottom, rgba(10,14,26,.7) 0%, transparent 100%);
    }

    .logo-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo-wrap img {
      height: 44px;
      width: auto;
      filter: brightness(0) invert(1);
      opacity: .92;
    }

    .logo-wordmark {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 600;
      font-size: 20px;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--white);
      line-height: 1;
    }

    .logo-wordmark span {
      display: block;
      font-family: 'DM Mono', monospace;
      font-size: 9px;
      font-weight: 400;
      letter-spacing: .25em;
      color: var(--gold-lt);
      margin-top: 3px;
      text-transform: uppercase;
    }

    .header-nav {
      display: flex;
      align-items: center;
      gap: 32px;
    }

    .header-nav a {
      font-size: 11px;
      font-weight: 500;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(250,250,248,.65);
      text-decoration: none;
      transition: color .2s;
    }

    .header-nav a:hover { color: var(--white); }

    /* ─── HERO CONTENT ─── */
    .hero-content {
      position: fixed;
      inset: 0;
      z-index: 50;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 0 48px 72px;
      pointer-events: none;
    }

    /* destination eyebrow */
    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 18px;
      pointer-events: auto;
    }

    .eyebrow-line {
      width: 32px;
      height: 1px;
      background: var(--gold);
    }

    .eyebrow-text {
      font-family: 'DM Mono', monospace;
      font-size: 10px;
      font-weight: 400;
      letter-spacing: .3em;
      text-transform: uppercase;
      color: var(--gold-lt);
    }

    /* headline */
    .hero-headline {
      font-family: 'Cormorant Garamond', serif;
      font-weight: 300;
      font-size: clamp(52px, 7.5vw, 108px);
      line-height: .95;
      letter-spacing: -.01em;
      color: var(--white);
      margin-bottom: 24px;
      pointer-events: auto;
    }

    .hero-headline em {
      font-style: italic;
      color: var(--gold-lt);
    }

    /* sub */
    .hero-sub {
      font-size: 14px;
      font-weight: 300;
      color: rgba(250,250,248,.65);
      letter-spacing: .02em;
      line-height: 1.6;
      max-width: 360px;
      margin-bottom: 40px;
      pointer-events: auto;
    }

    /* CTA cluster */
    .cta-cluster {
      display: flex;
      align-items: center;
      gap: 20px;
      pointer-events: auto;
    }

    .btn-enter {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 14px 32px;
      background: var(--gold);
      color: var(--night);
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      text-decoration: none;
      border-radius: 2px;
      transition: background .2s, transform .2s var(--ease), box-shadow .2s;
      box-shadow: 0 4px 24px rgba(201,168,76,.35);
    }

    .btn-enter:hover {
      background: var(--gold-lt);
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(201,168,76,.45);
    }

    .btn-enter svg {
      transition: transform .2s var(--ease);
    }

    .btn-enter:hover svg {
      transform: translateX(3px);
    }

    .btn-ghost {
      font-size: 11px;
      font-weight: 500;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(250,250,248,.5);
      text-decoration: none;
      border-bottom: 1px solid rgba(250,250,248,.2);
      padding-bottom: 2px;
      transition: color .2s, border-color .2s;
    }

    .btn-ghost:hover {
      color: var(--white);
      border-color: rgba(250,250,248,.5);
    }

    /* ─── SIGNATURE: slide counter ─── */
    .slide-counter {
      position: fixed;
      right: 48px;
      bottom: 72px;
      z-index: 100;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
    }

    .counter-num {
      font-family: 'DM Mono', monospace;
      font-size: 11px;
      letter-spacing: .1em;
      color: rgba(250,250,248,.4);
      line-height: 1;
    }

    .counter-num strong {
      font-size: 18px;
      color: var(--white);
      font-weight: 400;
    }

    .counter-track {
      width: 1px;
      height: 64px;
      background: rgba(250,250,248,.15);
      position: relative;
      overflow: hidden;
    }

    .counter-progress {
      position: absolute;
      top: 0; left: 0; right: 0;
      background: var(--gold);
      height: 0%;
      transition: height 3s linear;
    }

    /* ─── SWIPER PAGINATION (dots) ─── */
    .swiper-pagination {
      position: fixed !important;
      bottom: 32px !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      z-index: 100;
    }

    .swiper-pagination-bullet {
      width: 5px !important;
      height: 5px !important;
      background: rgba(250,250,248,.35) !important;
      opacity: 1 !important;
      transition: width .3s, background .3s !important;
      border-radius: 2px !important;
    }

    .swiper-pagination-bullet-active {
      width: 22px !important;
      background: var(--gold) !important;
      border-radius: 2px !important;
    }

    /* ─── LOCATION CHIP ─── */
    .location-chip {
      position: fixed;
      top: 50%;
      right: 48px;
      transform: translateY(-50%) rotate(90deg);
      transform-origin: center center;
      z-index: 100;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .location-chip span {
      font-family: 'DM Mono', monospace;
      font-size: 9px;
      letter-spacing: .28em;
      text-transform: uppercase;
      color: rgba(250,250,248,.35);
    }

    .location-chip::before {
      content: '';
      width: 20px;
      height: 1px;
      background: rgba(250,250,248,.25);
    }

    /* ─── SWIPER NAV (hidden, keyboard/touch only) ─── */
    .swiper-button-next,
    .swiper-button-prev {
      opacity: 0;
      pointer-events: none;
    }

    /* ─── MOBILE ─── */
    @media (max-width: 768px) {
      .site-header { padding: 20px 24px; }
      .header-nav { display: none; }
      .hero-content { padding: 0 24px 80px; }
      .hero-headline { font-size: clamp(42px, 13vw, 64px); }
      .slide-counter { right: 24px; bottom: 80px; }
      .location-chip { right: 20px; }
      .logo-wrap img { height: 36px; }
    }

    /* ─── REDUCED MOTION ─── */
    @media (prefers-reduced-motion: reduce) {
      .swiper-slide img { transition: none; }
      .btn-enter, .btn-enter svg { transition: none; }
    }

    /* ─── FADE IN ─── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .eyebrow   { animation: fadeUp .8s var(--ease) .3s both; }
    .hero-headline { animation: fadeUp .9s var(--ease) .5s both; }
    .hero-sub  { animation: fadeUp .8s var(--ease) .7s both; }
    .cta-cluster { animation: fadeUp .8s var(--ease) .9s both; }
  </style>
</head>
<body>

<!-- ─── SLIDER ─── -->
<div class="swiper" id="mainSwiper">
  <div class="swiper-wrapper">
    <div class="swiper-slide"><img src="./assets/images/caro1.png" alt="Machu Picchu"></div>
    <div class="swiper-slide"><img src="./assets/images/caro2.png" alt="Valle Sagrado"></div>
    <div class="swiper-slide"><img src="./assets/images/caro3.png" alt="Cusco Ciudad"></div>
    <div class="swiper-slide"><img src="./assets/images/caro3.png" alt="Camino Inca"></div>
  </div>
  <div class="swiper-pagination"></div>
</div>

<!-- ─── HEADER ─── -->
<header class="site-header">
  <div class="logo-wrap">
    <img src="assets/images/prueba03.png" alt="KB Tours">
    <div class="logo-wordmark">
      KB Tours
      <span>Cusco · Perú</span>
    </div>
  </div>
  <nav class="header-nav">
    <a href="#">Nosotros</a>
    <a href="#">Destinos</a>
    <a href="#">Contacto</a>
  </nav>
</header>

<!-- ─── HERO COPY ─── -->
<div class="hero-content">
  <div class="eyebrow">
    <span class="eyebrow-line"></span>
    <span class="eyebrow-text">Experiencias Andinas · Desde 2010</span>
  </div>

  <h1 class="hero-headline">
    El Perú<br>
    <em>que pocos</em><br>
    conocen.
  </h1>

  <p class="hero-sub">
    Gestión de operaciones, grupos y contabilidad para el equipo KB Adventures.
  </p>

  <div class="cta-cluster">
    <a href="login.php" class="btn-enter">
      Ingresar al sistema
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
        <path d="M1 7h12M8 2l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </a>
    <a href="#" class="btn-ghost">Conocer más</a>
  </div>
</div>

<!-- ─── SIDE LOCATION ─── -->
<div class="location-chip">
  <span>3,400 msnm · Cusco</span>
</div>

<!-- ─── SLIDE COUNTER ─── -->
<div class="slide-counter">
  <div class="counter-num">
    <strong id="slideNum">01</strong> / 04
  </div>
  <div class="counter-track">
    <div class="counter-progress" id="progressBar"></div>
  </div>
</div>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
<script>
  const totalSlides = 4;
  const autoDelay   = 4000;

  const swiper = new Swiper('#mainSwiper', {
    slidesPerView: 1,
    loop: true,
    speed: 1000,
    effect: 'fade',
    fadeEffect: { crossFade: true },
    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    pagination: { el: '.swiper-pagination', clickable: true },
    autoplay: { delay: autoDelay, disableOnInteraction: false },
    keyboard: { enabled: true },
    a11y: true,
  });

  // Counter + progress bar
  const slideNumEl  = document.getElementById('slideNum');
  const progressEl  = document.getElementById('progressBar');

  function updateCounter(swiper) {
    const real = ((swiper.realIndex) % totalSlides) + 1;
    slideNumEl.textContent = String(real).padStart(2, '0');
    // reset & animate progress bar
    progressEl.style.transition = 'none';
    progressEl.style.height = '0%';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        progressEl.style.transition = `height ${autoDelay}ms linear`;
        progressEl.style.height = '100%';
      });
    });
  }

  swiper.on('slideChange', updateCounter);
  updateCounter(swiper);
</script>
</body>
</html>
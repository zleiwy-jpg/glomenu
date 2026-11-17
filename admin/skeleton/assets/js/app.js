(() => {
  // Loader 6 saniyədə açılır; media yüklənməsi bu vaxtdan sonra da davam edir.
  const siteLoader = document.getElementById('siteLoader');
  const loaderBar = document.getElementById('loaderBar');
  const loaderRing = document.getElementById('loaderRingFill');
  const CIRC = 339.292; // r=54
  const reduceMotion = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  const dishVideoQueue = [];
  const dishVideoItems = new WeakMap();
  const dishVideoItemsBySrc = new Map();
  let activeDishVideoPreload = null;
  [...document.querySelectorAll('.dish')].forEach((dish) => {
    const src = dish.dataset.video || '';
    if (!src) return;
    let item = dishVideoItemsBySrc.get(src);
    if (!item) {
      const video = dish.querySelector('.dish-photo video') || document.createElement('video');
      item = { src, video, finished: false };
      dishVideoItemsBySrc.set(src, item);
      dishVideoQueue.push(item);
    }
    dishVideoItems.set(dish, item);
  });
  const preloadNextDishVideo = () => {
    if (activeDishVideoPreload) return;
    let item;
    while ((item = dishVideoQueue.shift())) {
      const video = item.video;
      if (video.error || (video.getAttribute('src') === item.src && video.readyState >= 3)) {
        item.finished = true;
        continue;
      }
      break;
    }
    if (!item) return;
    const video = item.video;
    activeDishVideoPreload = item;
    video.dataset.backgroundPreload = '1';
    let done = false;
    let timeout;
    const finish = () => {
      if (done) return;
      done = true;
      clearTimeout(timeout);
      video.removeEventListener('canplay', finish);
      video.removeEventListener('error', finish);
      item.finished = true;
      activeDishVideoPreload = null;
      preloadNextDishVideo();
    };
    video.addEventListener('canplay', finish, { once: true });
    video.addEventListener('error', finish, { once: true });
    timeout = setTimeout(finish, 10000);
    try {
      video.preload = 'auto';
      delete video.dataset.preloadLazy;
      if (video.getAttribute('src') !== item.src) video.setAttribute('src', item.src);
      video.load();
    } catch {
      finish();
    }
  };
  const prioritizeDishVideo = (dish) => {
    const item = dishVideoItems.get(dish);
    if (!item || item.finished || item === activeDishVideoPreload) return;
    const index = dishVideoQueue.indexOf(item);
    if (index > 0) {
      dishVideoQueue.splice(index, 1);
      dishVideoQueue.unshift(item);
    }
  };
  const setRing = p => {
    p = Math.min(1, Math.max(0, p));
    if (loaderRing) loaderRing.style.strokeDashoffset = (CIRC * (1 - p)).toFixed(1);
    if (loaderBar) loaderBar.setAttribute('aria-valuenow', String(Math.round(p * 100)));
  };
  const openSite = () => {
    if (!siteLoader || siteLoader.dataset.opened) return;
    siteLoader.dataset.opened = '1';
    setRing(1);
    siteLoader.classList.add('complete');
    setTimeout(() => {
      siteLoader.classList.add('done');
      document.body.classList.remove('loading');
      setTimeout(() => siteLoader.remove(), 500);
    }, completeMs);
  };
  const completeMs = reduceMotion ? 60 : 700;
  const fillDurationMs = 6000;
  preloadNextDishVideo();
  if (!siteLoader) {
    document.body.classList.remove('loading');
  } else {
    setRing(0);
    const t0 = performance.now();
    let finished = false;
    const tick = () => {
      if (finished) return;
      const el = performance.now() - t0;
      const progress = Math.min(1, el / fillDurationMs);
      setRing(progress);
      if (progress >= 1) {
        finished = true;
        openSite();
        return;
      }
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
    setTimeout(() => { if (!finished) { finished = true; openSite(); } }, fillDurationMs);
  }

  const searchInput = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearSearch');
  const emptyState = document.getElementById('emptyState');
  const resetBtn = document.getElementById('resetSearch');
  const dishes = [...document.querySelectorAll('.dish')];
  const sections = [...document.querySelectorAll('.cat-section')];
  const pills = [...document.querySelectorAll('.cat-pill')];
  const searchHint = document.getElementById('searchHint');
  const baseHint = searchHint ? searchHint.textContent : '';

  function normalize(s) {
    return (s || '').toLowerCase()
      .replace(/ə/g, 'e').replace(/ı/g, 'i').replace(/ö/g, 'o')
      .replace(/ü/g, 'u').replace(/ç/g, 'c').replace(/ş/g, 's').replace(/ğ/g, 'g');
  }

  function applySearch() {
    if (!searchInput) return;
    const q = normalize(searchInput.value.trim());
    if (clearBtn) clearBtn.hidden = !q;
    let visibleCount = 0;
    dishes.forEach(d => {
      const hit = !q || normalize(d.dataset.name).includes(q);
      d.classList.toggle('hidden', !hit);
      if (hit) visibleCount++;
    });
    sections.forEach(sec => {
      const vis = sec.querySelectorAll('.dish:not(.hidden)').length;
      sec.style.display = vis ? '' : 'none';
      const countEl = sec.querySelector('.cat-count');
      if (countEl) countEl.textContent = vis + ' yemək';
    });
    if (emptyState) emptyState.hidden = visibleCount !== 0;
    if (searchHint) {
      searchHint.textContent = q ? visibleCount + ' nəticə tapıldı' : baseHint;
    }
  }

  if (searchInput) searchInput.addEventListener('input', applySearch);
  if (clearBtn) clearBtn.addEventListener('click', () => {
    searchInput.value = '';
    applySearch();
    searchInput.focus();
  });
  if (resetBtn) resetBtn.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    applySearch();
  });

  const nav = document.getElementById('catnavScroll');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        const id = e.target.dataset.section;
        pills.forEach(p => p.classList.toggle('active', p.dataset.cat === id));
        const active = document.querySelector('.cat-pill.active');
        if (active && nav) nav.scrollTo({ left: active.offsetLeft - 16, behavior: 'smooth' });
      }
    });
  }, { rootMargin: '-40% 0px -55% 0px' });
  sections.forEach(s => io.observe(s));

  pills.forEach(p => p.addEventListener('click', () => {
    pills.forEach(x => x.classList.remove('active'));
    p.classList.add('active');
  }));

  const dishOverlay = document.getElementById('dishOverlay');
  const dishCloseBtn = document.getElementById('dishClose');
  const dishImg = document.getElementById('dishImg');
  const dishName = document.getElementById('dishName');
  const dishWeight = document.getElementById('dishWeight');
  const dishDesc = document.getElementById('dishDesc');
  const dishIng = document.getElementById('dishIng');
  const dishPrice = document.getElementById('dishPrice');
  const dishPhotoWrap = document.getElementById('dishPhotoWrap');
  const dishModal = document.getElementById('dishModal');
  const dishDetails = document.getElementById('dishDetails');
  const dishDetailsToggle = document.getElementById('dishDetailsToggle');
  const dishDetailsBack = document.getElementById('dishDetailsBack');
  const dishModalX = document.getElementById('dishModalX');
  const dishRateBtn = document.getElementById('dishRateBtn');
  const dishReportBtn = document.getElementById('dishReportBtn');
  const dishRateForm = document.getElementById('dishRateForm');
  const dishReportForm = document.getElementById('dishReportForm');
  const dishStars = document.getElementById('dishStars');
  const dishRateSend = document.getElementById('dishRateSend');
  const dishRateOk = document.getElementById('dishRateOk');
  const dishRateErr = document.getElementById('dishRateErr');
  const dishReasons = document.getElementById('dishReasons');
  const dishReportText = document.getElementById('dishReportText');
  const dishReportSend = document.getElementById('dishReportSend');
  const dishReportOk = document.getElementById('dishReportOk');
  const dishReportErr = document.getElementById('dishReportErr');
  let currentDish = { id: 0, name: '' };
  let currentStars = 0;
  let currentReason = '';
  // Video ↔ alt panel sinxron animasiya kilidi (CSS 1s ilə eyni)
  let dishAnimLock = false;
  const DISH_ANIM_MS = 1000;
  // Cari video (modal üçün)
  let currentVideo = '';
  let dishRevTimer = null;
  // Video sürəti: "Tərkibə bax"da normal sürət — 1x
  const DISH_VIDEO_RATE = 1;
  function applyDishRate(v) {
    if (!v) return;
    const r = v._dishRate || DISH_VIDEO_RATE;
    try { v.defaultPlaybackRate = r; } catch {}
    try { v.playbackRate = r; } catch {}
  }
  // Sürət elementin ömrü boyu qorunur (seek/load/autoplay-dan sonra da sabit qalır)
  function hookDishRate(v, rate) {
    if (!v || v._rateHooked) return;
    v._rateHooked = true;
    v._dishRate = rate || DISH_VIDEO_RATE;
    const fix = () => { if (v.playbackRate !== v._dishRate) applyDishRate(v); };
    ['loadedmetadata', 'playing', 'seeked', 'ratechange'].forEach(ev => v.addEventListener(ev, fix));
    applyDishRate(v);
  }
  hookDishRate(document.getElementById('dishVideo'), DISH_VIDEO_RATE);

  // ---- Tərs oynatma: "Tərkibə bax"da video irəli oynayarkən kadrlar buferə yığılır,
  // "Tərkibi bağla"da həmin kadrlar canvas-da geriyə oynadılır. Fayl lazım deyil. ----
  const REV_FRAME_MAX = 1200; // yaddaş limiti (~40s@30fps); dolanda kadrlar seyrəlir
  let revFrames = [];       // {mt, b} — mt: mediaTime, b: JPEG Blob (artan sıra ilə)
  let revFrameSkip = 1;
  let revCapSession = 0;
  let revCapStop = null;
  let revCapBusy = 0;
  let revSeq = 0;
  let revWorker = null;
  let revWorkerUrl = '';
  function revSupports() {
    try {
      if (typeof Worker === 'undefined' || typeof OffscreenCanvas === 'undefined' || typeof createImageBitmap !== 'function') return false;
      if (typeof HTMLVideoElement.prototype.requestVideoFrameCallback !== 'function') return false;
      const oc = new OffscreenCanvas(2, 2);
      if (typeof oc.transferToImageBitmap !== 'function') return false;
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return false;
      return true;
    } catch { return false; }
  }
  function revWorkerEnsure() {
    if (revWorker) return revWorker;
    const src = 'let oc=null,cx=null,q=.85;' +
      'onmessage=async function(e){var d=e.data||{};' +
      'if(d.init){oc=new OffscreenCanvas(d.w,d.h);cx=oc.getContext("2d");q=d.q||.85;return;}' +
      'if(!cx||!d.bmp)return;' +
      'try{cx.drawImage(d.bmp,0,0,oc.width,oc.height);if(d.bmp.close)d.bmp.close();' +
      'var b=await oc.convertToBlob({type:"image/jpeg",quality:q});' +
      'postMessage({id:d.id,s:d.s,mt:d.mt,b:b});}catch(err){postMessage({id:d.id,s:d.s,err:1});}};';
    try {
      revWorkerUrl = URL.createObjectURL(new Blob([src], { type: 'application/javascript' }));
      revWorker = new Worker(revWorkerUrl);
    } catch { revWorker = null; return null; }
    revWorker.onmessage = (e) => {
      revCapBusy = Math.max(0, revCapBusy - 1);
      const d = e.data || {};
      if (d.err || !d.b || d.s !== revCapSession) return;
      if (revFrames.length >= REV_FRAME_MAX) revFrames = revFrames.filter((_, i) => (i % 2) === 0);
      revFrames.push({ mt: d.mt, b: d.b });
      revFrameSkip = revFrames.length >= REV_FRAME_MAX ? 2 : 1;
    };
    return revWorker;
  }
  function revStopCapture() {
    revCapSession++;
    if (revCapStop) { try { revCapStop(); } catch {} revCapStop = null; }
    revCapBusy = 0;
  }
  function revStartCapture(dv) {
    if (!dv || !revSupports()) return;
    revStopCapture();
    const s = revCapSession;
    const vw = dv.videoWidth || 0, vh = dv.videoHeight || 0;
    if (!vw || !vh) return;
    const sc = Math.min(1, 720 / Math.max(vw, vh));
    const cw = Math.max(2, Math.round(vw * sc) & ~1);
    const ch = Math.max(2, Math.round(vh * sc) & ~1);
    const w = revWorkerEnsure();
    let ctx = null;
    try { ctx = new OffscreenCanvas(cw, ch).getContext('2d'); } catch { ctx = null; }
    if (!w || !ctx) return;
    const oc = ctx.canvas;
    w.postMessage({ init: 1, w: cw, h: ch, q: 0.85 });
    if (revCapSession === s) { revFrames = []; revFrameSkip = 1; }
    let lastMt = -1, stopped = false;
    revCapStop = () => { stopped = true; };
    const step = () => {
      if (stopped || s !== revCapSession) return;
      try {
        dv.requestVideoFrameCallback((now, meta) => {
          if (stopped || s !== revCapSession) return;
          try {
            const mt = (meta && isFinite(meta.mediaTime)) ? meta.mediaTime : (isFinite(dv.currentTime) ? dv.currentTime : 0);
            const minGap = revFrameSkip > 1 ? (0.02 * revFrameSkip) : 0.02;
            if (mt >= lastMt + minGap && revCapBusy < 4) {
              lastMt = mt;
              ctx.drawImage(dv, 0, 0, cw, ch);
              const bmp = oc.transferToImageBitmap();
              revCapBusy++;
              w.postMessage({ id: ++revSeq, s, mt, bmp }, [bmp]);
            }
          } catch {}
          step();
        });
      } catch {}
    };
    step();
  }
  function revFrameIndexAt(frames, mt) {
    let lo = 0, hi = frames.length - 1, ans = 0;
    while (lo <= hi) {
      const mid = (lo + hi) >> 1;
      if (frames[mid].mt <= mt) { ans = mid; lo = mid + 1; } else hi = mid - 1;
    }
    return ans;
  }
  function revCanPlay(dv) {
    return !!dv && revSupports() && revFrames.length >= 2 && isFinite(dv.currentTime);
  }
  // Geriyə oynatma: mediaTime-əsaslı rAF döngəsi + createImageBitmap decode-ahead — donma yoxdur.
  // Panel qalxışı media vaxtı <= 1.1s qalanda (köhnə davranışla eyni) başlayır.
  function startRevPlayback(dv, revGen, panelReturn, finishRev) {
    let stopped = false;
    const bmps = [];
    const pending = new Set();
    const stopFn = () => {
      if (stopped) return;
      stopped = true;
      for (let k = 0; k < bmps.length; k++) { if (bmps[k]) { try { bmps[k].close(); } catch {} bmps[k] = null; } }
      pending.clear();
    };
    const vwrap = document.getElementById('dishVideoWrap');
    const frames = revFrames.slice().sort((a, b) => a.mt - b.mt);
    if (!vwrap || frames.length < 2) { try { finishRev(); } catch {} return stopFn; }
    let cv = document.getElementById('dishRevCanvas');
    if (!cv) {
      cv = document.createElement('canvas');
      cv.id = 'dishRevCanvas';
      cv.className = 'dish-rev-canvas';
      cv.setAttribute('aria-hidden', 'true');
      try { vwrap.appendChild(cv); } catch {}
    }
    try { cv.style.objectPosition = dv.style.objectPosition || ''; } catch {}
    const cctx = cv.getContext('2d');
    if (!cctx) { try { finishRev(); } catch {} return stopFn; }
    const total = revFrameIndexAt(frames, Math.min((isFinite(dv.currentTime) ? dv.currentTime : 0) + 0.05, frames[frames.length - 1].mt)) + 1;
    let i = total - 1;
    let sized = false;
    const prefetch = (k) => {
      if (k < 0 || k >= total || bmps[k] || pending.has(k)) return;
      pending.add(k);
      createImageBitmap(frames[k].b).then((bmp) => {
        pending.delete(k);
        if (stopped) { try { bmp.close(); } catch {} return; }
        bmps[k] = bmp;
        if (!sized) { sized = true; try { cv.width = bmp.width; cv.height = bmp.height; } catch {} }
      }).catch(() => { pending.delete(k); });
    };
    for (let k = 0; k < 6; k++) prefetch(i - k);
    let panelArmed = false;
    const maybePanel = (mt) => {
      if (panelArmed) return;
      if (mt <= 1.1) { panelArmed = true; try { panelReturn(); } catch {} }
    };
    const durAt = (k) => {
      const d = k > 0 ? (frames[k].mt - frames[k - 1].mt) : (frames.length > 1 ? (frames[1].mt - frames[0].mt) : 0.033);
      return Math.min(0.25, Math.max(0.015, d || 0.033)) * 1000;
    };
    const draw = () => {
      const bmp = bmps[i];
      if (!bmp) return;
      try { cctx.drawImage(bmp, 0, 0, cv.width, cv.height); } catch {}
    };
    let tFrame = performance.now();
    let lenFrame = durAt(i);
    let startedAt = performance.now();
    const boot = () => {
      if (stopped || (dv && dv._revGen !== revGen)) return;
      if (bmps[i]) {
        draw();
        try { vwrap.classList.add('show-rev'); } catch {}
        maybePanel(frames[i].mt);
        setTimeout(() => {
          if (stopped || (dv && dv._revGen !== revGen)) return;
          try { dv.pause(); if (!isFinite(dv.currentTime) || dv.currentTime > 0.05) dv.currentTime = 0; } catch {}
        }, 450);
        tFrame = performance.now();
        requestAnimationFrame(tick);
        return;
      }
      if (performance.now() - startedAt > 500) { try { finishRev(); } catch {} return; }
      requestAnimationFrame(boot);
    };
    const tick = (now) => {
      if (stopped || (dv && dv._revGen !== revGen)) return;
      let guard = 0;
      while (i > 0 && now - tFrame >= lenFrame && guard < 40) {
        tFrame += lenFrame;
        i--;
        lenFrame = durAt(i);
        guard++;
        for (let k = 1; k <= 5; k++) prefetch(i - k);
        if (bmps[i + 3]) { try { bmps[i + 3].close(); } catch {} bmps[i + 3] = null; }
      }
      if (bmps[i]) draw();
      maybePanel(frames[i].mt);
      if (i === 0 && now - tFrame >= lenFrame) { try { finishRev(); } catch {} return; }
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(boot);
    return stopFn;
  }

  function parseIngredients(dishEl, descText) {
    try {
      const raw = dishEl.dataset.ing;
      if (raw) {
        const arr = JSON.parse(raw);
        if (Array.isArray(arr) && arr.length) return arr.map(String);
      }
    } catch {}
    return (descText || '')
      .split(/[,;]/)
      .flatMap(s => s.split(/[—–-]/))
      .map(s => s.trim().replace(/[.]+$/, ''))
      .filter(s => s.length > 1)
      .slice(0, 12);
  }

  function openDish(dishEl) {
    if (!dishOverlay) return;
    prioritizeDishVideo(dishEl);
    const img = dishEl.querySelector('.dish-photo img');
    const name = (dishEl.querySelector('h3') || {}).textContent || '';
    const desc = (dishEl.querySelector('.dish-desc') || {}).textContent || '';
    const weight = (dishEl.querySelector('.weight') || {}).textContent || '';
    const price = (dishEl.querySelector('.price') || {}).textContent || '';
    const hasVideo = !!(dishEl.dataset.video || '');
    if (dishImg) {
      if (!hasVideo && img && img.src) {
        dishImg.src = img.src;
        dishImg.alt = name;
        if (dishPhotoWrap) dishPhotoWrap.hidden = false;
      } else {
        dishImg.removeAttribute('src');
        dishImg.alt = '';
        if (dishPhotoWrap) dishPhotoWrap.hidden = true;
      }
    }
    if (dishName) dishName.textContent = name;
    if (dishWeight) { dishWeight.textContent = weight; dishWeight.hidden = !weight; }
    if (dishDesc) dishDesc.textContent = desc;
    if (dishPrice) dishPrice.textContent = price;
    currentDish = { id: parseInt((dishEl.dataset.id || '0'), 10) || 0, name };
    if (dishModal) {
      const bc = dishEl.dataset.border || '';
      dishModal.style.borderColor = (/^#[0-9a-fA-F]{6}$/.test(bc)) ? bc : '';
      dishModal.classList.toggle('has-video', hasVideo);
    }
    resetFeedbackForms();
    openRateForm(false); // qiymət paneli modal açılanda həmişə açıq olur
    if (dishIng) {
      const list = parseIngredients(dishEl, desc);
      dishIng.innerHTML = '';
      list.forEach(ing => {
        const chip = document.createElement('span');
        chip.className = 'tag';
        chip.textContent = ing;
        dishIng.appendChild(chip);
      });
    }
    const dishVideoWrap = document.getElementById('dishVideoWrap');
    const dishVideo = document.getElementById('dishVideo');
    const videoSrc = hasVideo ? (dishEl.dataset.video || '') : '';
    if (dishVideoWrap && dishVideo) {
      if (videoSrc) {
        const cardImg = dishEl.querySelector('.dish-photo img');
        // Poster = kartdakı ilk kadr şəkli (admin paneldə videodan tutulub, 16:10 crop ilə).
        // Kartda <video> varsa (köhnə məhsul, poster yoxdur) → data-poster-ə bax, yoxdursa boş.
        let poster = (cardImg && cardImg.src && cardImg.src.indexOf('data:') !== 0) ? cardImg.src : '';
        if (!poster && dishEl.dataset.poster) {
          try {
            const a = document.createElement('a');
            a.href = dishEl.dataset.poster;
            poster = a.href;
          } catch { poster = dishEl.dataset.poster; }
        }
        if (poster) dishVideo.setAttribute('poster', poster);
        else dishVideo.removeAttribute('poster');
        // Kartdakı crop-u modal videoya da tətbiq et (eyni fokus) — kvadrat konteynerdə də eyni hissə görünsün
        try {
          const cx = dishEl.dataset.coverx || '50';
          const cy = dishEl.dataset.covery || dishEl.dataset.coverpos || '50';
          dishVideo.style.objectPosition = cx + '% ' + cy + '%';
        } catch {}
        if (dishVideo.getAttribute('src') !== videoSrc) {
          dishVideo.setAttribute('src', videoSrc);
          try { dishVideo.preload = 'auto'; } catch {}
          try { dishVideo.load(); } catch {}
          try {
            const pre0 = dishVideo;
            const onPre0 = () => { try { pre0.removeEventListener('loadeddata', onPre0); } catch {} try { if (pre0.getAttribute('src') === videoSrc) pre0.currentTime = 0; } catch {} };
            pre0.addEventListener('loadeddata', onPre0);
          } catch {}
        }
dishVideoWrap.hidden = false;
        try {
          dishVideo.muted = true;
          dishVideo.loop = false;
          dishVideo.playsInline = true;
          dishVideo.currentTime = 0;
          dishVideo.pause();
        } catch {}
        // Tərs oynatma vəziyyəti sıfırlanır — bufer "Tərkibə bax"da video irəli oynayarkən yenidən yığılır
        currentVideo = videoSrc;
        try { dishVideo._revFinish = null; } catch {}
        try { dishVideo._revGen = (dishVideo._revGen || 0) + 1; } catch {}
        try { dishVideoWrap.classList.remove('show-rev'); } catch {}
        revStopCapture();
        revFrames = [];
        revFrameSkip = 1;
        const revCv0 = document.getElementById('dishRevCanvas');
        if (revCv0) { try { revCv0.remove(); } catch {} }
        // Modal açılır: video dayandırılmış, ilk kadrda qalır
        if (dishModal) dishModal.classList.remove('playing');
        if (dishModal) dishModal.classList.remove('video-ended');
        if (dishDetails) { try { dishDetails.scrollTop = 0; } catch {} dishDetails.classList.remove('hiding'); dishDetails.classList.remove('returning'); dishDetails.classList.remove('entering'); void dishDetails.offsetWidth; dishDetails.classList.add('entering'); setTimeout(() => { if (dishDetails) dishDetails.classList.remove('entering'); }, 500); }
        if (dishDetailsBack) { dishDetailsBack.classList.remove('show'); dishDetailsBack.classList.remove('show-ended'); }
        if (dishDetailsToggle) { dishDetailsToggle.disabled = false; dishDetailsToggle.setAttribute('aria-expanded', 'false'); }
        if (dishDetailsBack) dishDetailsBack.disabled = false;
        dishAnimLock = false;
      } else {
        try { dishVideo.pause(); } catch {}
        dishVideo.removeAttribute('src');
        try { dishVideo.load(); } catch {}
        dishVideoWrap.hidden = true;
      }
    }
    dishOverlay.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeDish() {
    if (!dishOverlay) return;
    resetFeedbackForms();
    if (dishRevTimer) { clearTimeout(dishRevTimer); dishRevTimer = null; }
    currentVideo = '';
    if (dishModal) dishModal.style.borderColor = '';
    if (dishModal) dishModal.classList.remove('has-video');
    if (dishModal) dishModal.classList.remove('playing');
    if (dishModal) dishModal.classList.remove('video-ended');
    if (dishDetails) { dishDetails.classList.remove('hiding'); dishDetails.classList.remove('returning'); dishDetails.classList.remove('entering'); }
    if (dishDetailsBack) { dishDetailsBack.classList.remove('show'); dishDetailsBack.classList.remove('show-ended'); }
    const dishVideoEl = document.getElementById('dishVideo');
    try { if (dishVideoEl) dishVideoEl._revFinish = null; } catch {}
    try { if (dishVideoEl) dishVideoEl._revGen = (dishVideoEl._revGen || 0) + 1; } catch {}
    const dishVideoWrapEl = document.getElementById('dishVideoWrap');
    if (dishVideoEl) {
      try { dishVideoEl.pause(); } catch {}
      dishVideoEl.removeAttribute('src');
      dishVideoEl.removeAttribute('poster');
      try { dishVideoEl.style.objectPosition = ''; } catch {}
      try { dishVideoEl.load(); } catch {}
    }
    if (dishVideoWrapEl) dishVideoWrapEl.hidden = true;
    revStopCapture();
    revFrames = [];
    revFrameSkip = 1;
    try { if (dishVideoWrapEl) dishVideoWrapEl.classList.remove('show-rev'); } catch {}
    const revCv1 = document.getElementById('dishRevCanvas');
    if (revCv1) { try { revCv1.remove(); } catch {} }
    dishOverlay.hidden = true;
    document.body.style.overflow = '';
  }

  dishes.forEach(d => {
    d.addEventListener('click', () => openDish(d));
    d.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDish(d); }
    });
  });
  // Kartda posteri olmayan köhnə videolar: ilk qeyri-qara kadrı göstər + lazy preload.
  // (Yeni məhsullarda poster <img> olur — bu kod onlara toxunmur.)
  try {
    const cardVids = [...document.querySelectorAll('.dish-photo video')];
    const ensureFirstFrame = (v) => {
      if (!v || v._ffDone) return;
      v._ffDone = true;
      try { v.muted = true; } catch {}
      const show01 = () => { try { if (v.duration && v.duration > 0.15) v.currentTime = 0.1; } catch {} };
      if (v.readyState >= 1) show01();
      else v.addEventListener('loadedmetadata', show01, { once: true });
    };
    if ('IntersectionObserver' in window) {
      const vio = new IntersectionObserver((es) => {
        es.forEach((e) => {
          if (!e.isIntersecting) return;
          const v = e.target;
          vio.unobserve(v);
          ensureFirstFrame(v);
        });
      }, { rootMargin: '300px' });
      cardVids.forEach((v) => vio.observe(v));
    } else {
      cardVids.forEach(ensureFirstFrame);
    }
  } catch {}
  if (dishCloseBtn) dishCloseBtn.addEventListener('click', closeDish);
  // "Tərkibə bax" → alt panel itənə kimi video ilə EYNİ müddətdə (.9s) fullscreen-ə açılır.
  // Video openDish-də preload/play olunduğu üçün keçid axıcıdır (donma olmur).
  // "Tərkibə bax" → .playing class ilə video tam ekrana, panel aşağı itir (1s ease-in-out)
  function setDishExpanded(expanded) {
    if (dishAnimLock) return;
    const isVideoModal = dishModal && dishModal.classList.contains('has-video');
    if (!isVideoModal) return;
    const currently = dishModal.classList.contains('playing');
    if (expanded === currently) return;
    dishAnimLock = true;
    if (dishDetailsToggle) dishDetailsToggle.disabled = true;
    if (dishDetailsBack) dishDetailsBack.disabled = true;
    const dv = document.getElementById('dishVideo');
    const showBackAfterEnd = () => {
      if (dv && dv._revFinish) return;
      if (dishModal && dishModal.classList.contains('playing')) {
        dishModal.classList.add('video-ended');
        if (dishDetailsBack) {
          dishDetailsBack.classList.remove('show');
          dishDetailsBack.classList.remove('show-ended');
          void dishDetailsBack.offsetWidth;
          dishDetailsBack.classList.add('show');
          dishDetailsBack.classList.add('show-ended');
        }
      }
    };
    if (expanded) {
      if (dishDetails) { dishDetails.classList.remove('returning'); dishDetails.classList.add('hiding'); }
      if (dishModal) { dishModal.classList.add('playing'); dishModal.classList.remove('video-ended'); }
      if (dishDetailsBack) { dishDetailsBack.classList.add('show'); dishDetailsBack.classList.remove('show-ended'); }
      if (dishDetailsToggle) dishDetailsToggle.setAttribute('aria-expanded', 'true');
      const vwrap0 = document.getElementById('dishVideoWrap');
      if (dv) {
        try { if (dishRevTimer) { clearTimeout(dishRevTimer); dishRevTimer = null; } } catch {}
        try { dv._revFinish = null; } catch {}
        try { dv._revGen = (dv._revGen || 0) + 1; } catch {}
        // Poster yalnız ilk kadr boyanandan sonra silinir — başlanğıcda qara ekran olmasın
        try {
          dv.addEventListener('playing', function dropPoster() {
            try { dv.removeEventListener('playing', dropPoster); } catch {}
            try { dv.removeAttribute('poster'); } catch {}
          }, { once: true });
        } catch {}
        try { if (vwrap0) vwrap0.classList.remove('show-rev'); } catch {}
        try {
          dv.muted = true; dv.loop = false;
          applyDishRate(dv);
          // 0-dan başlamırsa geri qaytar (artıq 0-dadırsa seek etmə — kadr boşalmasın)
          if (!isFinite(dv.currentTime) || dv.currentTime > 0.05) { try { dv.currentTime = 0; } catch {} }
          const pr = dv.play(); if (pr && pr.catch) pr.catch(() => { showBackAfterEnd(); });
        } catch { showBackAfterEnd(); }
        // İrəli oynayış boyu kadrlar geriyə oynatma üçün buferə yığılır (worker JPEG — əsas axını saxlamır)
        revStartCapture(dv);
        if (!dv._endedHandlerAdded) {
          dv._endedHandlerAdded = true;
          dv.addEventListener('ended', showBackAfterEnd);
        }
      }
    } else if (revCanPlay(dv)) {
      // Kadr buferi canvas-da geriyə oynadılır (crossfade ilə) — donma yoxdur
      if (dishModal) { dishModal.classList.remove('video-ended'); }
      if (dishDetailsBack) { dishDetailsBack.classList.remove('show'); dishDetailsBack.classList.remove('show-ended'); }
      if (dishDetailsToggle) dishDetailsToggle.setAttribute('aria-expanded', 'false');
      try { dv.removeAttribute('poster'); } catch {}
      const revGen = (dv._revGen = (dv._revGen || 0) + 1);
      const vwrapRev = document.getElementById('dishVideoWrap');
      revStopCapture();
      let panelUp = false;
      const panelReturn = () => {
        if (panelUp || (dv && dv._revGen !== revGen)) return;
        panelUp = true;
        if (dishDetails) { dishDetails.classList.remove('hiding'); dishDetails.classList.add('returning'); }
        if (dishModal) { dishModal.classList.remove('playing'); }
      };
      let stopLoop = () => {};
      let finishDone = false;
      const finishRev = () => {
        if (dv && dv._revGen !== revGen) return;
        if (finishDone) return;
        finishDone = true;
        if (dishRevTimer) { clearTimeout(dishRevTimer); dishRevTimer = null; }
        panelReturn();
        try { stopLoop(); } catch {}
        if (dv) {
          try { dv.pause(); if (!isFinite(dv.currentTime) || dv.currentTime > 0.05) dv.currentTime = 0; } catch {}
          dv._revFinish = null;
        }
        try { if (vwrapRev) vwrapRev.classList.remove('show-rev'); } catch {}
        if (dishModal) { dishModal.classList.remove('playing'); dishModal.classList.remove('video-ended'); }
        setTimeout(() => {
          dishAnimLock = false;
          if (dishDetails) dishDetails.classList.remove('returning');
          if (dishDetailsToggle) dishDetailsToggle.disabled = false;
          if (dishDetailsBack) dishDetailsBack.disabled = false;
        }, DISH_ANIM_MS);
      };
      dv._revFinish = finishRev;
      try { dv.pause(); } catch {}
      stopLoop = startRevPlayback(dv, revGen, panelReturn, finishRev);
      // Sərt taymer: qalıq media vaxtı + bufer
      try {
        const md2 = isFinite(dv.currentTime) ? dv.currentTime : 0;
        const segMs = Math.min(60000, Math.max(md2 * 1000 + 3000, 4000));
        dishRevTimer = setTimeout(() => { if (dv && typeof dv._revFinish === 'function') dv._revFinish(); }, segMs);
      } catch {
        dishRevTimer = setTimeout(() => { if (dv && typeof dv._revFinish === 'function') dv._revFinish(); }, 9000);
      }
      setTimeout(() => {
        dishAnimLock = false;
        if (dishDetailsToggle) dishDetailsToggle.disabled = false;
        if (dishDetailsBack) dishDetailsBack.disabled = false;
      }, DISH_ANIM_MS);
      return;
    } else {
      if (dishDetails) { dishDetails.classList.remove('hiding'); dishDetails.classList.add('returning'); }
      if (dishModal) { dishModal.classList.remove('playing'); dishModal.classList.remove('video-ended'); }
      if (dishDetailsBack) { dishDetailsBack.classList.remove('show'); dishDetailsBack.classList.remove('show-ended'); }
      if (dishDetailsToggle) dishDetailsToggle.setAttribute('aria-expanded', 'false');
      if (dv) {
        try { dv.pause(); dv.currentTime = 0; } catch {}
      }
    }
    setTimeout(() => {
      dishAnimLock = false;
      if (dishDetails) dishDetails.classList.remove('returning');
      if (dishDetailsToggle) dishDetailsToggle.disabled = false;
      if (dishDetailsBack) dishDetailsBack.disabled = false;
    }, DISH_ANIM_MS);
  }
  if (dishDetailsToggle) dishDetailsToggle.addEventListener('click', () => setDishExpanded(true));
  if (dishDetailsBack) dishDetailsBack.addEventListener('click', () => setDishExpanded(false));
  if (dishModalX) dishModalX.addEventListener('click', () => closeDish());

  function resetFeedbackForms() {
    currentStars = 0;
    currentReason = '';
    if (dishRateForm) dishRateForm.hidden = true;
    if (dishReportForm) dishReportForm.hidden = true;
    if (dishRateOk) dishRateOk.hidden = true;
    if (dishRateErr) dishRateErr.hidden = true;
    if (dishReportOk) dishReportOk.hidden = true;
    if (dishReportErr) dishReportErr.hidden = true;
    if (dishReportText) dishReportText.value = '';
    if (dishRateBtn) dishRateBtn.classList.remove('on');
    if (dishReportBtn) dishReportBtn.classList.remove('on');
    paintStars(0);
    if (dishReasons) [...dishReasons.querySelectorAll('button')].forEach(b => b.classList.remove('on'));
    if (dishRateSend) { dishRateSend.disabled = false; dishRateSend.textContent = 'Göndər'; }
    if (dishReportSend) { dishReportSend.disabled = false; dishReportSend.textContent = 'Şikayəti göndər'; }
  }

  function paintStars(v) {
    if (!dishStars) return;
    [...dishStars.querySelectorAll('button')].forEach(b => {
      b.classList.toggle('lit', +b.dataset.v <= v);
    });
  }

  function dishMasa() {
    try { return (document.body.dataset.masa || '').replace(/[^0-9]/g, ''); } catch { return ''; }
  }

  if (dishRateBtn) dishRateBtn.addEventListener('click', () => {
    if (!dishRateForm || !dishReportForm) return;
    const willShow = dishRateForm.hidden;
    resetFeedbackForms();
    if (willShow) openRateForm(true);
  });
  // "Qiymət ver" paneli modal açılanda həmişə açıq olur
  function openRateForm(scroll) {
    if (!dishRateForm || !dishReportForm) return;
    let voted = 0;
    try { voted = +(localStorage.getItem('rated_dish_' + currentDish.id) || 0); } catch {}
    dishRateForm.hidden = false;
    if (dishRateBtn) dishRateBtn.classList.add('on');
    if (voted > 0) {
      paintStars(voted);
      if (dishRateOk) { dishRateOk.hidden = false; dishRateOk.textContent = '✓ Siz artıq ' + voted + ' ulduz vermisiniz. Təşəkkür edirik!'; }
      if (dishRateSend) dishRateSend.disabled = true;
    }
    if (scroll !== false) dishRateForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  if (dishStars) dishStars.addEventListener('click', (e) => {
    const b = e.target.closest ? e.target.closest('button[data-v]') : null;
    if (!b) return;
    currentStars = +b.dataset.v || 0;
    paintStars(currentStars);
    if (dishRateErr) dishRateErr.hidden = true;
  });
  if (dishRateSend) dishRateSend.addEventListener('click', async () => {
    if (dishRateErr) dishRateErr.hidden = true;
    if (!currentStars) {
      if (dishRateErr) { dishRateErr.hidden = false; dishRateErr.textContent = 'Zəhmət olmasa ulduz seçin.'; }
      return;
    }
    dishRateSend.disabled = true;
    dishRateSend.textContent = 'Göndərilir…';
    try {
      const res = await fetch('api/rate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dish: currentDish.id, name: currentDish.name, stars: currentStars, table: dishMasa() })
      });
      const data = await res.json();
      if (data.ok) {
        try { localStorage.setItem('rated_dish_' + currentDish.id, String(currentStars)); } catch {}
        if (dishRateOk) { dishRateOk.hidden = false; dishRateOk.textContent = '✓ Səsiniz qeydə alındı. Təşəkkür edirik!'; }
      } else if (dishRateErr) {
        dishRateErr.hidden = false;
        dishRateErr.textContent = data.error || 'Xəta oldu.';
        dishRateSend.disabled = false;
        dishRateSend.textContent = 'Göndər';
      }
    } catch {
      if (dishRateErr) { dishRateErr.hidden = false; dishRateErr.textContent = 'Xəta oldu, yenidən cəhd edin.'; }
      dishRateSend.disabled = false;
      dishRateSend.textContent = 'Göndər';
    }
  });
  if (dishReportBtn) dishReportBtn.addEventListener('click', () => {
    if (!dishRateForm || !dishReportForm) return;
    const willShow = dishReportForm.hidden;
    resetFeedbackForms();
    if (willShow) {
      dishReportForm.hidden = false;
      dishReportBtn.classList.add('on');
      dishReportForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });
  if (dishReasons) dishReasons.addEventListener('click', (e) => {
    const b = e.target.closest ? e.target.closest('button[data-r]') : null;
    if (!b) return;
    currentReason = b.dataset.r || '';
    [...dishReasons.querySelectorAll('button')].forEach(x => x.classList.toggle('on', x === b));
    if (dishReportErr) dishReportErr.hidden = true;
  });
  if (dishReportSend) dishReportSend.addEventListener('click', async () => {
    if (dishReportErr) dishReportErr.hidden = true;
    if (!currentReason) {
      if (dishReportErr) { dishReportErr.hidden = false; dishReportErr.textContent = 'Zəhmət olmasa səbəb seçin.'; }
      return;
    }
    dishReportSend.disabled = true;
    dishReportSend.textContent = 'Göndərilir…';
    try {
      const res = await fetch('api/report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dish: currentDish.id, name: currentDish.name, reason: currentReason, text: dishReportText ? dishReportText.value.trim() : '', table: dishMasa() })
      });
      const data = await res.json();
      if (data.ok) {
        if (dishReportOk) dishReportOk.hidden = false;
      } else if (dishReportErr) {
        dishReportErr.hidden = false;
        dishReportErr.textContent = data.error || 'Xəta oldu.';
        dishReportSend.disabled = false;
        dishReportSend.textContent = 'Şikayəti göndər';
      }
    } catch {
      if (dishReportErr) { dishReportErr.hidden = false; dishReportErr.textContent = 'Xəta oldu, yenidən cəhd edin.'; }
      dishReportSend.disabled = false;
      dishReportSend.textContent = 'Şikayəti göndər';
    }
  });
  if (dishOverlay) dishOverlay.addEventListener('click', (e) => { if (e.target === dishOverlay) closeDish(); });

  const wifiBtn = document.getElementById('wifiBtn');
  if (wifiBtn) wifiBtn.addEventListener('click', async () => {
    const wifi = wifiBtn.dataset.wifi || '';
    toast('Wi-Fi: ' + wifi);
    if (wifi && navigator.clipboard) {
      try { await navigator.clipboard.writeText(wifi); } catch {}
    }
  });

  const toTop = document.getElementById('toTop');
  if (toTop) {
    window.addEventListener('scroll', () => {
      toTop.classList.toggle('show', window.scrollY > 700);
    }, { passive: true });
    toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  const overlay = document.getElementById('waiterOverlay');
  const closeBtn = document.getElementById('waiterClose');
  const sendBtn = document.getElementById('waiterSend');
  const tableSel = document.getElementById('tableNo');
  const noteInput = document.getElementById('waiterNote');
  const okMsg = document.getElementById('waiterOk');
  const errMsg = document.getElementById('waiterErr');

  const masaFromBody = document.body.dataset.masa;
  if (masaFromBody && tableSel) {
    const opt = [...tableSel.options].find(o => o.value === masaFromBody);
    if (opt) tableSel.value = masaFromBody;
  }

  function openWaiter() {
    if (!overlay) return;
    overlay.hidden = false;
    document.body.style.overflow = 'hidden';
    if (okMsg) okMsg.hidden = true;
    if (errMsg) errMsg.hidden = true;
  }
  function closeWaiter() {
    if (!overlay) return;
    overlay.hidden = true;
    document.body.style.overflow = '';
  }

  const waiterFab = document.getElementById('waiterFab');
  const waiterBtnTop = document.getElementById('waiterBtnTop');
  if (waiterFab) waiterFab.addEventListener('click', openWaiter);
  if (waiterBtnTop) waiterBtnTop.addEventListener('click', openWaiter);
  if (closeBtn) closeBtn.addEventListener('click', closeWaiter);
  if (overlay) overlay.addEventListener('click', (e) => { if (e.target === overlay) closeWaiter(); });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (dishOverlay && !dishOverlay.hidden) closeDish();
    else if (overlay && !overlay.hidden) closeWaiter();
  });

  if (sendBtn) sendBtn.addEventListener('click', async () => {
    if (okMsg) okMsg.hidden = true;
    if (errMsg) errMsg.hidden = true;
    sendBtn.disabled = true;
    sendBtn.textContent = 'Göndərilir…';
    try {
      const res = await fetch('api/call-waiter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ table: tableSel ? tableSel.value : '', note: noteInput ? noteInput.value.trim() : '' })
      });
      const data = await res.json();
      if (data.ok) {
        if (okMsg) okMsg.hidden = false;
        setTimeout(closeWaiter, 2200);
      } else if (errMsg) { errMsg.hidden = false; errMsg.textContent = data.error || 'Xəta oldu, zəhmət olmasa yenidən cəhd edin.'; }
    } catch {
      if (errMsg) errMsg.hidden = false;
    } finally {
      sendBtn.disabled = false;
      sendBtn.textContent = 'Çağırışı göndər';
    }
  });

  function toast(msg) {
    let t = document.getElementById('appToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'appToast';
      t.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:999;background:#1C1917;color:#fff;font:600 13.5px Inter,sans-serif;padding:12px 18px;border-radius:999px;box-shadow:0 10px 30px rgba(0,0,0,.35);max-width:90vw;text-align:center';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.display = 'block';
    clearTimeout(t._h);
    t._h = setTimeout(() => { t.style.display = 'none'; }, 3200);
  }
})();

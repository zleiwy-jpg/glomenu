<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Menyu Konstruktoru — gloMenu</title>
<meta name="description" content="Öz menyunu hazırla: dil seçimi, video hero, açılış ekranı və digər parametrlərlə canlı menyu konstruktoru.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Inter+Tight:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="builder.css?v=2.7">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23E5484D'/><text x='50' y='70' font-size='54' text-anchor='middle' fill='white' font-family='Arial' font-weight='bold'>g</text></svg>">
<meta name="theme-color" content="#090C10">
</head>
<body>

<div class="b-app">
  <!-- SOL PANEL: parametrlər -->
  <aside class="b-side" id="bSide">
    <a class="b-back" href="../">← gloMenu</a>
    <h1>Öz menyunu hazırla</h1>
    <p class="b-sub">Parametrləri seç — sağdakı nümunə menyu canlı yenilənsin.</p>

    <label class="b-field">
      <span>Restoran adı</span>
      <input id="optName" type="text" value="Demo Restoran" maxlength="40" autocomplete="off">
    </label>

    <div class="b-group">
      <p class="b-gtitle">Menyu imkanları</p>
      <label class="b-check"><input type="checkbox" id="optLang" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Dil seçimi<small>Qlobus + bayraq menyusu · 4 dil</small></span></label>
      <label class="b-check"><input type="checkbox" id="optLoader" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Açılış ekranı<small>Loading page</small></span></label>
      <label class="b-check"><input type="checkbox" id="optVideo" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Video hero<small>Şəkil əvəzinə video</small></span></label>
      <label class="b-check"><input type="checkbox" id="optQuick" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Tez keçidlər<small>Wi-Fi · Lokasiya · Əlaqə</small></span></label>
      <label class="b-check"><input type="checkbox" id="optSearch" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Axtarış<small>Yemək axtarışı</small></span></label>
      <label class="b-check"><input type="checkbox" id="optCats" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Kateqoriyalar<small>Bölmə naviqasiyası</small></span></label>
      <label class="b-check"><input type="checkbox" id="optPrices" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Qiymətlər<small>Qiymət göstərilsin</small></span></label>
    </div>

    <div class="b-group">
      <p class="b-gtitle">Rənglər</p>
      <div class="b-colors">
        <label class="b-color"><input type="color" id="optBg" value="#000000"><span>Arxa fon</span></label>
        <label class="b-color"><input type="color" id="optAccent" value="#B91C1C"><span>Düymələr</span></label>
        <label class="b-color"><input type="color" id="optCatbg" value="#000000"><span>Kateqoriya</span></label>
        <label class="b-color"><input type="color" id="optQicon" value="#0C0B09"><span>İkonlar</span></label>
      </div>
    </div>

    <div class="b-group">
      <p class="b-gtitle">Görünüş</p>
      <label class="b-check"><input type="checkbox" id="optPatterns"><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Naxışlar<small>Nöqtəli fon naxışı</small></span></label>
      <label class="b-check"><input type="checkbox" id="optPhotos" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Yemək şəkilləri<small>Foto göstərilsin</small></span></label>
      <label class="b-check"><input type="checkbox" id="optTopbar" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Üst zolaq<small>powered by sətri</small></span></label>
      <label class="b-check"><input type="checkbox" id="optFooter" checked><span class="box" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><span class="lbl">Alt bilgi<small>Footer sətri</small></span></label>
    </div>

    <button class="b-cta" id="openMenu" type="button">Öz menyunu hazırla
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </button>
    <p class="b-hint">Düymə seçilmiş parametrlərlə menyunu yeni pəncərədə açır.</p>
  </aside>

  <!-- SAĞ: mobil mockup içində canlı menyu -->
  <main class="b-main">
    <div>
      <div class="b-phone">
      <div class="iphone" aria-label="Mobil menyu önizləməsi">
        <span class="ip-btn ip-action"></span>
        <span class="ip-btn ip-vol-up"></span>
        <span class="ip-btn ip-vol-down"></span>
        <span class="ip-btn ip-power"></span>
        <span class="ip-btn ip-camera"></span>
        <div class="iphone-screen">
          <div class="ip-island"></div>
          <div class="ip-status">
            <span>9:41</span>
            <span class="ip-status-icons">
              <svg viewBox="0 0 20 14" fill="currentColor" aria-hidden="true"><rect x="0" y="9" width="3" height="5" rx="1"/><rect x="5" y="6" width="3" height="8" rx="1"/><rect x="10" y="3" width="3" height="11" rx="1"/><rect x="15" y="0" width="3" height="14" rx="1" opacity=".4"/></svg>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><path d="M12 20h.01"/></svg>
              <svg viewBox="0 0 28 14" fill="none" stroke="currentColor" aria-hidden="true"><rect x="1" y="1.5" width="22" height="11" rx="3" stroke-width="1.5"/><rect x="3" y="3.5" width="15" height="7" rx="1.5" fill="currentColor" stroke="none"/><path d="M25.5 5v4" stroke-width="1.8" stroke-linecap="round"/></svg>
            </span>
          </div>
          <div class="ip-screen-scroll"><iframe id="pvFrame" class="pv-frame" title="Menyu önizləmə"></iframe></div>
          <div class="ip-home"></div>
        </div>
      </div>
      <div class="b-order-row"><button class="b-order" id="orderMenu" type="button">Menyunu sifariş et<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button></div>
      </div>
  </main>
</div>

<script src="builder.js?v=2.0" defer></script>
</body>
</html>

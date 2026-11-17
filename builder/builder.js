/* gloMenu builder — sidebar parametrləri → telefondakı real önizləməsi (iframe) */
(function(){
'use strict';

/* Reload başdan açılsın (scroll bərpası yox) */
try{ if('scrollRestoration' in history) history.scrollRestoration = 'manual'; }catch(e){}
window.scrollTo(0, 0);
window.addEventListener('load', function(){ try{ window.scrollTo(0, 0); }catch(e){} });

var DEF = {name:'Demo Restoran', lang:'az', bg:'#000000', ac:'#B91C1C', catbg:'', qicon:'', loader:1, video:1, quick:1, search:1, cats:1, prices:1, showLang:1, patterns:0, photos:1, topbar:1, footer:1};

function num(v){ return v === '1' ? 1 : 0; }

function hexStr(v){ v = String(v || ''); return /^#[0-9a-fA-F]{6}$/.test(v) ? v : ''; }

function readParams(){
  var q = new URLSearchParams(location.search);
  var s = {name: q.get('name') || DEF.name, lang: q.get('lang') || DEF.lang};
  s.bg = hexStr(q.get('bg')) || DEF.bg;
  s.ac = hexStr(q.get('ac')) || DEF.ac;
  s.catbg = hexStr(q.get('catbg')) || '';
  s.qicon = hexStr(q.get('qicon')) || '';
  ['loader','video','quick','search','cats','prices','showLang','patterns','photos','topbar','footer'].forEach(function(k){
    s[k] = q.has(k) ? num(q.get(k)) : DEF[k];
  });
  if(['az','en','ru','tr'].indexOf(s.lang) === -1) s.lang = 'az';
  return s;
}

var S = readParams();

function $(id){ return document.getElementById(id); }

function previewURL(){
  var p = new URLSearchParams({
    name: S.name, lang: S.lang, theme: 1, custom: 1, bg: S.bg, ac: S.ac, catbg: S.catbg, qicon: S.qicon,
    loader: S.loader, video: S.video, quick: S.quick,
    search: S.search, cats: S.cats, prices: S.prices, showLang: S.showLang,
    stars: 0, patterns: S.patterns, photos: S.photos, topbar: S.topbar, footer: S.footer
  });
  return 'preview.php?' + p.toString();
}

var frame = $('pvFrame'), timer = null;
function refresh(){ if(frame) frame.src = previewURL(); }
function refreshSoon(){ clearTimeout(timer); timer = setTimeout(refresh, 600); }

/* Önizləmə: 390px real mobil ölçünü mockup ekranına miqyasla */
var DESIGN_W = 390;
function fitPreview(){
  var wrap = document.querySelector('.ip-screen-scroll');
  if(!wrap || !frame) return;
  var w = wrap.clientWidth, h = wrap.clientHeight;
  if(!w || !h) return;
  var s = w / DESIGN_W;
  frame.style.width = DESIGN_W + 'px';
  frame.style.height = Math.round(h / s) + 'px';
  frame.style.transform = 'scale(' + s + ')';
}
window.addEventListener('resize', fitPreview);
window.addEventListener('load', fitPreview);

function syncSide(){
  $('optName').value = S.name;
  $('optLang').checked = !!S.showLang;
  $('optLoader').checked = !!S.loader;
  $('optVideo').checked = !!S.video;
  $('optQuick').checked = !!S.quick;
  $('optSearch').checked = !!S.search;
  $('optCats').checked = !!S.cats;
  $('optPrices').checked = !!S.prices;
  $('optPatterns').checked = !!S.patterns;
  $('optPhotos').checked = !!S.photos;
  $('optTopbar').checked = !!S.topbar;
  $('optFooter').checked = !!S.footer;
  $('optBg').value = S.bg;
  $('optAccent').value = S.ac;
  $('optCatbg').value = S.catbg || '#000000';
  $('optQicon').value = S.qicon || '#0C0B09';
}

function bindSide(){
  $('optName').addEventListener('input', function(){
    S.name = this.value.trim() || DEF.name;
    refreshSoon();
  });
  [['optLang','showLang'],['optLoader','loader'],['optVideo','video'],['optQuick','quick'],
   ['optSearch','search'],['optCats','cats'],['optPrices','prices'],
   ['optPatterns','patterns'],['optPhotos','photos'],['optTopbar','topbar'],['optFooter','footer']].forEach(function(pair){
    $(pair[0]).addEventListener('change', function(){
      S[pair[1]] = this.checked ? 1 : 0;
      refresh();
    });
  });
  $('openMenu').addEventListener('click', function(){
    window.open(previewURL(), '_blank');
  });
  [['optBg','bg'],['optAccent','ac'],['optCatbg','catbg'],['optQicon','qicon']].forEach(function(pair){
    $(pair[0]).addEventListener('input', function(){
      if(hexStr(this.value)) S[pair[1]] = this.value;
      refreshSoon();
    });
  });
  var orderBtn = $('orderMenu');
  if(orderBtn) orderBtn.addEventListener('click', function(){
    var feats = [['Dil seçimi',S.showLang],['Açılış ekranı',S.loader],['Video hero',S.video],
      ['Tez keçidlər',S.quick],['Axtarış',S.search],['Kateqoriyalar',S.cats],['Qiymətlər',S.prices],
      ['Naxışlar',S.patterns],['Yemək şəkilləri',S.photos],['Üst zolaq',S.topbar],['Alt bilgi',S.footer]];
    var lines = ['Restoran: ' + S.name, 'Rənglər: fon ' + S.bg + ', düymə ' + S.ac + ', kateqoriya ' + (S.catbg || 'standart') + ', ikon ' + (S.qicon || 'standart'), 'Dil: ' + String(S.lang).toUpperCase(), ''];
    feats.forEach(function(f){ lines.push((f[1] ? '[x] ' : '[ ] ') + f[0]); });
    try { lines.push('', 'Önizləmə: ' + new URL(previewURL(), location.href).href); } catch(e){}
    location.href = 'mailto:info@glomenu.az?subject=' + encodeURIComponent('Menyu sifarişi — ' + S.name)
      + '&body=' + encodeURIComponent(lines.join('\n'));
  });
}

syncSide(); bindSide(); fitPreview(); refresh();
})();

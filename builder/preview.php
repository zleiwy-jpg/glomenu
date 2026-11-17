<?php
// Hostingdə mod_deflate sönülü olsa belə HTML gzip ilə getsin (sürətli ilk bayt).
if (!ini_get('zlib.output_compression') && function_exists('ob_gzhandler')) { @ob_start('ob_gzhandler'); }
// gloMenu builder preview — skelet şablonunun eynisi, nümunə parametrlərlə
function pvFlag($k, $d = 1) {
  if (!isset($_GET[$k])) return $d;
  $v = $_GET[$k];
  return ($v === '1' || $v === 1 || $v === true || $v === 'on') ? 1 : 0;
}
$PV = [
  'loader'   => pvFlag('loader', 1),
  'video'    => pvFlag('video', 1),
  'quick'    => pvFlag('quick', 1),
  'search'   => pvFlag('search', 1),
  'cats'     => pvFlag('cats', 1),
  'prices'   => pvFlag('prices', 1),
  'showLang' => pvFlag('showLang', 1),
  'patterns' => pvFlag('patterns', 0),
  'photos'   => pvFlag('photos', 1),
  'topbar'   => pvFlag('topbar', 1),
  'footer'   => pvFlag('footer', 1),
  'theme'    => (isset($_GET['theme']) && in_array((int)$_GET['theme'], [1,2,3,4,5,6,7,8], true) ? (int)$_GET['theme'] : 1),
  'custom'   => pvFlag('custom', 0),
  'bg'       => (isset($_GET['bg']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$_GET['bg']) ? (string)$_GET['bg'] : ''),
  'ac'       => (isset($_GET['ac']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$_GET['ac']) ? (string)$_GET['ac'] : ''),
  'catbg'    => (isset($_GET['catbg']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$_GET['catbg']) ? (string)$_GET['catbg'] : ''),
  'qicon'    => (isset($_GET['qicon']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$_GET['qicon']) ? (string)$_GET['qicon'] : ''),
  'lang'     => (isset($_GET['lang']) && in_array($_GET['lang'], ['az','en','ru','tr'], true) ? $_GET['lang'] : 'az'),
  'name'     => (isset($_GET['name']) && trim($_GET['name']) !== '' ? mb_substr(trim($_GET['name']), 0, 40) : 'Demo Restoran'),
];
// Tema kombinasiyaları (arxa fon + aksent birlikdə)
$THEMES = [
  1 => ['paper' => '#000000', 'card' => '#1C1915', 'card2' => '#242019', 'line' => '#2C2620', 'lineSoft' => '#231F19', 'accent' => '#B91C1C', 'accentDark' => '#7F1D1D', 'dark' => '#12100D', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
  2 => ['paper' => '#04070F', 'card' => '#0B1220', 'card2' => '#101A2E', 'line' => '#1B2A45', 'lineSoft' => '#141E33', 'accent' => '#1D4ED8', 'accentDark' => '#1E3A8A', 'dark' => '#070C16', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
  3 => ['paper' => '#0A0614', 'card' => '#150E24', 'card2' => '#1C1330', 'line' => '#2C2145', 'lineSoft' => '#211A36', 'accent' => '#7C3AED', 'accentDark' => '#5B21B6', 'dark' => '#0D0918', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
  4 => ['paper' => '#04100C', 'card' => '#0A1A14', 'card2' => '#0E211A', 'line' => '#1A3329', 'lineSoft' => '#142820', 'accent' => '#059669', 'accentDark' => '#065F46', 'dark' => '#07120E', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
  5 => ['paper' => '#0D0A04', 'card' => '#161006', 'card2' => '#1E1609', 'line' => '#33290F', 'lineSoft' => '#251F0C', 'accent' => '#D9A419', 'accentDark' => '#8A6700', 'dark' => '#0A0803', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
  6 => ['paper' => '#030D12', 'card' => '#08141B', 'card2' => '#0B1B24', 'line' => '#16303C', 'lineSoft' => '#10242E', 'accent' => '#06B6D4', 'accentDark' => '#0E7490', 'dark' => '#02090D', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
  7 => ['paper' => '#12060D', 'card' => '#1D0B14', 'card2' => '#26101B', 'line' => '#3D1E2C', 'lineSoft' => '#2E1622', 'accent' => '#EC4899', 'accentDark' => '#BE185D', 'dark' => '#0D0409', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
  8 => ['paper' => '#100803', 'card' => '#1A0E06', 'card2' => '#231208', 'line' => '#3A2210', 'lineSoft' => '#2C1A0C', 'accent' => '#EA580C', 'accentDark' => '#9A3412', 'dark' => '#0C0602', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
];
function pv_hex_ok($s) { return is_string($s) && preg_match('/^#[0-9a-fA-F]{6}$/', $s); }
function pv_hex_mix($a, $b, $w) {
  $a = ltrim($a, '#'); $b = ltrim($b, '#');
  if (strlen($a) === 3) $a = $a[0].$a[0].$a[1].$a[1].$a[2].$a[2];
  if (strlen($b) === 3) $b = $b[0].$b[0].$b[1].$b[1].$b[2].$b[2];
  $o = '';
  for ($i = 0; $i < 3; $i++) {
    $v = round(hexdec(substr($a, $i * 2, 2)) * $w + hexdec(substr($b, $i * 2, 2)) * (1 - $w));
    $o .= sprintf('%02X', max(0, min(255, (int)$v)));
  }
  return '#' . $o;
}
function pv_hex_lum($hex) {
  $hex = ltrim($hex, '#');
  if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  $c = [hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255];
  foreach ($c as $k => $v) $c[$k] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
  return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}
function pv_palette($base, $bg, $ac) {
  $p = $base;
  if (pv_hex_ok($bg)) {
    $p['paper'] = $bg;
    $p['card'] = pv_hex_mix($bg, '#FFFFFF', 0.93);
    $p['card2'] = pv_hex_mix($bg, '#FFFFFF', 0.89);
    $p['line'] = pv_hex_mix($bg, '#FFFFFF', 0.82);
    $p['lineSoft'] = pv_hex_mix($bg, '#FFFFFF', 0.90);
    $p['dark'] = pv_hex_mix($bg, '#000000', 0.65);
    if (pv_hex_lum($bg) > 0.45) { $p['ink'] = '#14181D'; $p['muted'] = '#4B5563'; $p['muted2'] = '#6B7280'; }
    else { $p['ink'] = '#F2EBE0'; $p['muted'] = '#A89E90'; $p['muted2'] = '#6E665C'; }
  }
  if (pv_hex_ok($ac)) {
    $p['accent'] = $ac;
    $p['accentDark'] = pv_hex_mix($ac, '#000000', 0.65);
  }
  return $p;
}
$TH = pv_palette(
  $THEMES[$PV['theme']],
  !empty($PV['custom']) ? $PV['bg'] : '',
  !empty($PV['custom']) ? $PV['ac'] : ''
);
function pvUrl($lang) {
  $q = $_GET; $q['lang'] = $lang;
  return '?' . http_build_query($q);
}
// Nümunə tərcümələr (EN/RU/TR) — AZ real menyudur
$TR = [
  'cats' => [
    'burger' => ['en' => 'Burgers', 'ru' => 'Бургеры', 'tr' => 'Burger ve Dönerler'],
    'pizza'  => ['en' => 'Pizzas', 'ru' => 'Пиццы', 'tr' => 'Pizzalar'],
    'salat'  => ['en' => 'Salads', 'ru' => 'Салаты', 'tr' => 'Salatalar'],
    'esas'   => ['en' => 'Main Dishes', 'ru' => 'Основные блюда', 'tr' => 'Ana Yemekler'],
    'asiya'  => ['en' => 'Asian Cuisine', 'ru' => 'Азиатская кухня', 'tr' => 'Suşi ve Asya'],
    'desert' => ['en' => 'Desserts', 'ru' => 'Десерты', 'tr' => 'Tatlılar'],
  ],
  'items' => [
    4  => ['name' => ['en' => 'Classic Cheeseburger', 'ru' => 'Классический чизбургер', 'tr' => 'Klasik Cheeseburger'],
           'desc' => ['en' => 'Beef patty, cheddar, lettuce, tomato', 'ru' => 'Говяжья котлета, чеддер, салат, помидор', 'tr' => 'Dana köfte, cheddar peyniri, marul, domates']],
    5  => ['name' => ['en' => 'Pepperoni Pizza', 'ru' => 'Пицца Пепперони', 'tr' => 'Pepperoni Pizza'],
           'desc' => ['en' => 'Pepperoni, mozzarella, tomato sauce', 'ru' => 'Пепперони, моцарелла, томатный соус', 'tr' => 'Pepperoni, mozzarella, domates sosu']],
    6  => ['name' => ['en' => 'Caesar Salad', 'ru' => 'Салат Цезарь', 'tr' => 'Sezar Salata'],
           'desc' => ['en' => 'Chicken fillet, iceberg, parmesan', 'ru' => 'Куриное филе, айсберг, пармезан', 'tr' => 'Tavuk fileto, aysberg marul, parmesan']],
    7  => ['name' => ['en' => 'Chicken Pasta', 'ru' => 'Паста с курицей', 'tr' => 'Kremalı Tavuklu Makarna'],
           'desc' => ['en' => 'Fettuccine, chicken, cream, parmesan', 'ru' => 'Феттуччине, курица, сливки, пармезан', 'tr' => 'Fettuccine, tavuk fileto, krema, parmesan']],
    8  => ['name' => ['en' => 'Grilled Steak', 'ru' => 'Стейк на гриле', 'tr' => 'Izgara Steak'],
           'desc' => ['en' => 'Entrecote, grilled vegetables, butter', 'ru' => 'Антрекот, овощи гриль, масло', 'tr' => 'Dana antrikot, ızgara sebze, tereyağı']],
    9  => ['name' => ['en' => 'Sushi Set', 'ru' => 'Суши-сет', 'tr' => 'Suşi Set'],
           'desc' => ['en' => 'Philadelphia, California, tuna', 'ru' => 'Филадельфия, калифорния, тунец', 'tr' => 'Philadelphia, California, susamlı ton balığı']],
    10 => ['name' => ['en' => 'Ramen', 'ru' => 'Рамен', 'tr' => 'Ramen Çorbası'],
           'desc' => ['en' => 'Noodles, chicken broth, egg', 'ru' => 'Лапша, куриный бульон, яйцо', 'tr' => 'Yumurtalı noodle, tavuk suyu, yumurta']],
    11 => ['name' => ['en' => 'Berry Pancakes', 'ru' => 'Панкейки с ягодами', 'tr' => 'Meyveli Pankek'],
           'desc' => ['en' => 'Pancakes, strawberry, syrup', 'ru' => 'Панкейки, клубника, сироп', 'tr' => 'Pankek, çilek, akçaağaç şurubu']],
    12 => ['name' => ['en' => 'Strawberry Donut', 'ru' => 'Донат с клубникой', 'tr' => 'Çilekli Donut'],
           'desc' => ['en' => 'Donut, strawberry glaze', 'ru' => 'Донат, клубничная глазурь', 'tr' => 'Donut, çilek glazürü']],
    13 => ['name' => ['en' => 'Chocolate Fondant', 'ru' => 'Шоколадный фондан', 'tr' => 'Çikolatalı Fondan'],
           'desc' => ['en' => 'Dark chocolate, ice cream', 'ru' => 'Тёмный шоколад, мороженое', 'tr' => 'Bitter çikolata, vanilyalı dondurma']],
    3  => ['name' => ['en' => 'Caesar Salad', 'ru' => 'Салат Цезарь', 'tr' => 'Sezar Salata'],
           'desc' => ['en' => 'Cheese, croutons, chicken, tomato', 'ru' => 'Сыр, сухарики, курица, помидор', 'tr' => 'Rendelenmiş peynir, kruton, tavuk fileto']],
  ],
  'ui' => [
    'en' => ['searchPh' => 'Search dishes…', 'wifi' => 'Wi-Fi', 'loc' => 'Location', 'contact' => 'Contact'],
    'ru' => ['searchPh' => 'Поиск блюд…', 'wifi' => 'Wi-Fi', 'loc' => 'Локация', 'contact' => 'Контакты'],
    'tr' => ['searchPh' => 'Yemek ara…', 'wifi' => 'Wi-Fi', 'loc' => 'Konum', 'contact' => 'İletişim'],
  ],
];
$dataFile = dirname(__DIR__) . '/admin/skeleton/data/demo-menu.json';
$data = json_decode(file_get_contents($dataFile), true);
$restaurant = $data['restaurant'];
$restaurant['name'] = $PV['name'];
$categories = $data['categories'];
$items = $data['items'];
if ($PV['lang'] !== 'az') {
  $L = $PV['lang'];
  foreach ($categories as &$c) {
    if (isset($TR['cats'][$c['id']][$L])) $c['name'] = $TR['cats'][$c['id']][$L];
  }
  unset($c);
  foreach ($items as &$it) {
    $id = (int)($it['id'] ?? 0);
    if (isset($TR['items'][$id]['name'][$L])) $it['name'] = $TR['items'][$id]['name'][$L];
    if (isset($TR['items'][$id]['desc'][$L])) $it['desc'] = $TR['items'][$id]['desc'][$L];
  }
  unset($it);
}
$T = $PV['lang'] === 'az'
  ? ['searchPh' => 'Yemək axtar — məs. burger, pizza, donut…', 'wifi' => 'Wi-Fi', 'loc' => 'Lokasiya', 'contact' => 'Əlaqə']
  : $TR['ui'][$PV['lang']];
$masa = isset($_GET['masa']) ? preg_replace('/[^0-9]/', '', $_GET['masa']) : (isset($_GET['table']) ? preg_replace('/[^0-9]/', '', $_GET['table']) : '');
function itemsByCat($items, $catId) {
  return array_values(array_filter($items, function($i) use ($catId) { return $i['cat'] === $catId; }));
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($PV['lang']) ?>">
<head>
<base href="../admin/skeleton/">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars($restaurant['name']) ?> — Menyu | gloMenu</title>
<meta name="description" content="<?= htmlspecialchars($restaurant['name']) ?> menyusu — səhər yeməyi, salatlar, əsas yeməklər, pizza, şirniyyat və içkilər. QR menyu gloMenu ilə.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link rel="dns-prefetch" href="https://fonts.googleapis.com">
<link rel="preload" as="image" href="assets/img/hero-poster.webp" fetchpriority="high" type="image/webp">
<!-- Hero videosu bilərəkdən preload edilmir: əvvəlcə mətn+poster boyanır -->
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"></noscript>
<link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(dirname(__DIR__) . '/admin/skeleton/assets/css/style.css') ?: '8.4' ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%231C1917'/><text x='50' y='68' font-size='52' text-anchor='middle' fill='white' font-family='Georgia'>g</text><circle cx='78' cy='72' r='8' fill='%23C2410C'/></svg>">
<meta name="theme-color" content="#1C1917">
<?php if(!$PV['prices']): ?><style>.price,.price.lg{display:none!important}</style><?php endif; ?>
<?php if(!$PV['photos']): ?><style>.dish-photo{display:none!important}</style><?php endif; ?>
<style>:root{--paper:<?= $TH['paper'] ?>;--card:<?= $TH['card'] ?>;--card-2:<?= $TH['card2'] ?>;--line:<?= $TH['line'] ?>;--line-soft:<?= $TH['lineSoft'] ?>;--accent:<?= $TH['accent'] ?>;--accent-dark:<?= $TH['accentDark'] ?>;--dark:<?= $TH['dark'] ?>;--ink:<?= $TH['ink'] ?>;--muted:<?= $TH['muted'] ?>;--muted-2:<?= $TH['muted2'] ?>;--catbg:<?= $PV['catbg'] !== '' ? $PV['catbg'] : 'transparent' ?>;--qiconbg:<?= $PV['qicon'] !== '' ? $PV['qicon'] : '#0C0B09' ?>}</style>
<style>
/* Önizləmə mockup-u (~282px): menyu ölçüləri mockup-a uyğunlaşır */
/* Scrollbar gizli, scroll işləyir */
html{scrollbar-width:none;-ms-overflow-style:none}
body::-webkit-scrollbar{width:0;height:0;display:none}
/* Ölçülər real menyu ilə eynidir (390px real mobil endə render olunur) */
/* Yeni tab-da açılan demo desktop-da telefon enində görünsün */
@media(min-width:520px){
  html{background:#000}
  body{max-width:430px;margin:0 auto;box-shadow:0 0 0 1px var(--line),0 30px 90px rgba(0,0,0,.65);min-height:100vh}
}
</style>
</head>
<body data-masa="<?= htmlspecialchars($masa) ?>" data-lang="<?= htmlspecialchars($PV['lang']) ?>" class="loading">

<!-- Dil bayraqları (dairəvi flag ikonları üçün) -->
<svg width="0" height="0" style="position:absolute;overflow:hidden" aria-hidden="true">
  <defs>
    <symbol id="flag-az" viewBox="0 0 24 24">
      <rect width="24" height="8" fill="#00B5E2"/><rect y="8" width="24" height="8" fill="#EF3340"/><rect y="16" width="24" height="8" fill="#509E2F"/>
      <circle cx="11.2" cy="12" r="4.3" fill="#fff"/><circle cx="12.4" cy="12" r="3.5" fill="#EF3340"/>
      <polygon fill="#fff" points="14.8,10 15.13,11.21 16.21,10.59 15.59,11.67 16.8,12 15.59,12.33 16.21,13.41 15.13,12.79 14.8,14 14.47,12.79 13.39,13.41 14.01,12.33 12.8,12 14.01,11.67 13.39,10.59 14.47,11.21"/>
    </symbol>
    <symbol id="flag-en" viewBox="0 0 24 24">
      <rect width="24" height="24" fill="#012169"/>
      <path d="M0,0L24,24M24,0L0,24" stroke="#fff" stroke-width="4.5"/>
      <path d="M0,0L24,24M24,0L0,24" stroke="#C8102E" stroke-width="1.7"/>
      <path d="M12,0V24M0,12H24" stroke="#fff" stroke-width="7.5"/>
      <path d="M12,0V24M0,12H24" stroke="#C8102E" stroke-width="4.2"/>
    </symbol>
    <symbol id="flag-ru" viewBox="0 0 24 24">
      <rect width="24" height="8" fill="#fff"/><rect y="8" width="24" height="8" fill="#0039A6"/><rect y="16" width="24" height="8" fill="#D52B1E"/>
    </symbol>
    <symbol id="flag-tr" viewBox="0 0 24 24">
      <rect width="24" height="24" fill="#E30A17"/>
      <circle cx="9.4" cy="12" r="5" fill="#fff"/>
      <circle cx="10.4" cy="12" r="4" fill="#E30A17"/>
      <polygon fill="#fff" points="14,9.7 14.53,11.27 16.19,11.29 14.86,12.28 15.35,13.86 14,12.9 12.65,13.86 13.14,12.28 11.81,11.29 13.47,11.27"/>
    </symbol>
  </defs>
</svg>

<?php if($PV['patterns']): ?>
<div class="bg-pattern" aria-hidden="true"></div>
<?php endif; ?>

<?php if($PV['loader']): ?>
<!-- Loading screen: dairəvi ring + loqo, dolandan sonra fade ilə açılır -->
<div class="loader" id="siteLoader" aria-hidden="true">
  <div class="loader-stage">
    <div class="loader-core">
      <div class="loader-ring-wrap">
        <div class="loader-ring" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="loaderBar">
          <svg viewBox="0 0 120 120" aria-hidden="true">
            <circle class="ring-bg" cx="60" cy="60" r="54"></circle>
            <circle class="ring-fill" id="loaderRingFill" cx="60" cy="60" r="54"></circle>
          </svg>
          <div class="loader-g">gloMenu.</div>
        </div>
        <div class="loader-shadow" aria-hidden="true"></div>
      </div>
      <p class="loader-wish" aria-hidden="true">
        <svg class="wish-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V2"/><path d="M12 2v6"/><path d="M12 10v12"/></svg>
        <span class="wish-letters"><span>N</span><span>u</span><span>ş</span><span>&nbsp;</span><span>o</span><span>l</span><span>s</span><span>u</span><span>n</span><span>!</span></span>
        <svg class="wish-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 2c-1.8 2.6-3 6-3 9.5V14h3V2z"/><path d="M15.5 14v8"/></svg>
      </p>
    </div>
  </div>
</div>
<noscript><style>.loader{display:none!important}body.loading{overflow:auto!important}</style></noscript>
<?php endif; ?>

<?php if($PV['topbar']): ?>
<div class="topbar">
  <div class="wrap topbar-inner">
    <span class="powered"><i class="live-dot" aria-hidden="true"></i>powered by gloMenu.</span>
  </div>
</div>
<?php endif; ?>

<section class="hero">
  <div class="wrap">
    <div class="hero-card">
      <div class="hero-photo hero-video" id="heroVideo">
        <?php if($PV['video']): ?>
        <video id="heroBgVideo" autoplay muted loop playsinline preload="metadata" poster="assets/img/hero-poster.webp" disablepictureinpicture aria-hidden="true" tabindex="-1">
          <source src="../../assets/vid/hero.mp4" type="video/mp4">
        </video>
        <?php else: ?>
        <img src="assets/img/hero-poster.webp" alt="<?= htmlspecialchars($restaurant['name']) ?>" fetchpriority="high" decoding="async">
        <?php endif; ?>
        <?php if (!empty($restaurant['logo'])): ?>
        <div class="hero-logo" aria-hidden="true"><img src="<?= htmlspecialchars($restaurant['logo']) ?>" alt=""></div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($restaurant['name']) ?></h1>
      </div>
      <div class="hero-body">
        <?php if($PV['quick'] || $PV['showLang']): ?>
        <div class="quick-icons" aria-label="Tez keçidlər">
          <?php if($PV['quick']): ?>
          <button class="qicon-item" id="wifiBtn" type="button" data-wifi="<?= htmlspecialchars($restaurant['wifi']) ?>" aria-label="Wi-Fi şifrəsini göstər" title="<?= htmlspecialchars($restaurant['wifi']) ?>">
            <span class="qicon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><path d="M12 20h.01"/></svg>
            </span>
            <small><?= htmlspecialchars($T['wifi']) ?></small>
          </button>
          <a class="qicon-item" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($restaurant['address']) ?>" target="_blank" rel="noopener" aria-label="Məkanı xəritədə aç">
            <span class="qicon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </span>
            <small><?= htmlspecialchars($T['loc']) ?></small>
          </a>
          <a class="qicon-item" href="tel:<?= preg_replace('/[^+0-9]/','',$restaurant['phone']) ?>" aria-label="Zəng et: <?= htmlspecialchars($restaurant['phone']) ?>">
            <span class="qicon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 2z"/></svg>
            </span>
            <small><?= htmlspecialchars($T['contact']) ?></small>
          </a>
          <?php endif; ?>
          <?php if($PV['showLang']): ?>
          <div class="qicon-item lang-wrap">
            <button class="lang-btn" id="langBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Dil seçimi: Azərbaycan dili">
              <span class="qicon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/><path d="M2 12h20"/></svg>
              </span>
              <small id="langLbl">Dil</small>
            </button>
            <div class="lang-menu" id="langMenu" role="menu" aria-label="Dil seçimi">
              <button type="button" data-lang="az" role="menuitem"><span class="lm-flag"><svg aria-hidden="true"><use href="#flag-az"/></svg></span>Azərbaycanca<span class="lm-check">✓</span></button>
              <button type="button" data-lang="en" role="menuitem"><span class="lm-flag"><svg aria-hidden="true"><use href="#flag-en"/></svg></span>English<span class="lm-check">✓</span></button>
              <button type="button" data-lang="ru" role="menuitem"><span class="lm-flag"><svg aria-hidden="true"><use href="#flag-ru"/></svg></span>Русский<span class="lm-check">✓</span></button>
              <button type="button" data-lang="tr" role="menuitem"><span class="lm-flag"><svg aria-hidden="true"><use href="#flag-tr"/></svg></span>Türkçe<span class="lm-check">✓</span></button>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if($PV['search']): ?>
        <div class="hero-search">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" id="searchInput" placeholder="<?= htmlspecialchars($T['searchPh']) ?>" autocomplete="off" aria-label="Menyuda axtarış">
          <button id="clearSearch" type="button" hidden aria-label="Təmizlə">✕</button>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if($PV['cats']): ?>
<nav class="catnav" id="catnav" aria-label="Kateqoriyalar">
  <div class="wrap catnav-scroll" id="catnavScroll">
    <?php foreach($categories as $idx => $c): ?>
      <a href="#cat-<?= $c['id'] ?>" class="cat-pill<?= $idx===0 ? ' active' : '' ?>" data-cat="<?= $c['id'] ?>">
        <?= htmlspecialchars($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<main class="wrap menu" id="menuRoot">
  <div class="empty" id="emptyState" hidden>
    <p class="empty-title">Heç nə tapılmadı</p>
    <p class="empty-sub">Axtarışı dəyişib yenidən cəhd edin — məs. “burger”, “pizza”, “donut”.</p>
    <button class="btn-ghost" id="resetSearch" type="button">Axtarışı təmizlə</button>
  </div>

  <?php $imgCounter = 0; foreach($categories as $c):
    $list = itemsByCat($items, $c['id']);
    if(!$list) continue;
  ?>
  <section class="cat-section" id="cat-<?= $c['id'] ?>" data-section="<?= $c['id'] ?>">
    <div class="cat-head">
      <div>
        <h2><?= htmlspecialchars($c['name']) ?></h2>
        <?php if(!empty($c['note'])): ?><small><?= htmlspecialchars($c['note']) ?></small><?php endif; ?>
      </div>
    </div>

    <div class="dishes">
      <?php foreach($list as $it):
        // "video" sahəsi varsa modal avtomatik video rejimdə açılır
        // (Tərkibə bax → başlat, Tərkibi bağla → axıcı rewind)
        $dishVideo = $it['video'] ?? '';
        // Fərdi haşiyə rəngi (admin paneldən) — keçərsizdirsə standart
        $dishBorder = '';
        if (isset($it['border']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string)$it['border'])) {
          $dishBorder = strtoupper((string)$it['border']);
        }
        $ingAreaAttr = !empty($it['ingArea']) ? json_encode($it['ingArea'], JSON_UNESCAPED_UNICODE) : '';
        $ingSpotsAttr = !empty($it['ingSpots']) ? json_encode($it['ingSpots'], JSON_UNESCAPED_UNICODE) : '';
        // Videolu yemək: modalda yüksək keyfiyyətli tam kadr (-fm) göstərilir
        $dishPosterFull = '';
        $tmpImg = trim($it['img'] ?? '');
        if ($dishVideo !== '' && $tmpImg !== '') {
          $candFull = preg_replace('/(\.[^.]+)$/', '-fm$1', $tmpImg);
          if (@file_exists(dirname(__DIR__) . '/admin/skeleton/' . $candFull)) $dishPosterFull = $candFull;
        }
        // Videolu yemək kartı: ilk kadr posteri (admin paneldə videodan avtomatik tutulur).
        // Kartda 16:10 crop ilə <img>, modalda video posteri kimi görünür.
        $cardPoster = '';
        $cardPosterVer = '1';
        if ($dishVideo !== '') {
          $candP = !empty($it['poster']) ? $it['poster'] : preg_replace('/\.(mp4|webm)$/i', '-poster.jpg', $dishVideo);
          if ($candP !== '' && @is_file(dirname(__DIR__) . '/admin/skeleton/' . $candP)) {
            $cardPoster = $candP;
            $cardPosterVer = (@filemtime(dirname(__DIR__) . '/admin/skeleton/' . $candP) ?: '1');
          }
        }
        // Kart crop (admin paneldə vizual seçilir): X/Y fokus + zoom (köhnə coverPos → coverY).
        $coverX = isset($it['coverX']) ? max(0, min(100, (int)$it['coverX'])) : 50;
        if (isset($it['coverY'])) $coverY = max(0, min(100, (int)$it['coverY']));
        elseif (isset($it['coverPos'])) $coverY = max(0, min(100, (int)$it['coverPos']));
        else $coverY = 50;
        $coverZoom = isset($it['coverZoom']) ? max(100, min(250, (int)$it['coverZoom'])) : 100;
        $coverScale = $coverZoom > 100 ? number_format($coverZoom / 100, 3, '.', '') : '';
        $coverStyle = 'object-position:' . $coverX . '% ' . $coverY . '%' . ($coverScale !== '' ? ';transform:scale(' . $coverScale . ')' : '');
      ?>
      <article class="dish" tabindex="0" role="button" aria-label="<?= htmlspecialchars($it['name']) ?> — ətraflı bax" data-name="<?= htmlspecialchars(mb_strtolower($it['name'].' '.$it['desc'], 'UTF-8')) ?>" data-id="<?= $it['id'] ?>" data-video="<?= htmlspecialchars($dishVideo) ?>"<?= $dishBorder !== '' ? ' data-border="' . htmlspecialchars($dishBorder) . '" style="border-color:' . htmlspecialchars($dishBorder) . '"' : '' ?> data-ing-area="<?= htmlspecialchars($ingAreaAttr, ENT_QUOTES, 'UTF-8') ?>" data-ing-spots="<?= htmlspecialchars($ingSpotsAttr, ENT_QUOTES, 'UTF-8') ?>" data-ing='<?= htmlspecialchars(json_encode($it['ingredients'] ?? [], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>' data-cat="<?= $c['id'] ?>" data-coverpos="<?= $coverY ?>" data-coverx="<?= $coverX ?>" data-covery="<?= $coverY ?>" data-coverzoom="<?= $coverZoom ?>"<?= ($cardPoster !== '' ? ' data-poster="' . htmlspecialchars($cardPoster . '?v=' . $cardPosterVer) . '"' : ($dishPosterFull !== '' ? ' data-poster="' . htmlspecialchars($dishPosterFull) . '"' : '')) ?>>
        <?php
          $imgCounter++;
          $isEager = $imgCounter <= 4;
          $dishImg = trim($it['img'] ?? '');
          $dishWebp = '';
          if ($dishImg !== '' && strpos($dishImg, 'assets/') === 0) {
            $cand = preg_replace('/\.(jpe?g|png)$/i', '.webp', $dishImg);
            if ($cand !== $dishImg && @file_exists(dirname(__DIR__) . '/admin/skeleton/' . $cand)) $dishWebp = $cand;
          }
        ?>
        <div class="dish-photo<?= $dishVideo !== '' ? ' has-video' : '' ?>">
          <?php if ($cardPoster !== ''): ?>
            <img src="<?= htmlspecialchars($cardPoster) ?>?v=<?= $cardPosterVer ?>" alt="<?= htmlspecialchars($it['name']) ?>" <?= $isEager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" width="400" height="250" style="<?= $coverStyle ?>">
          <?php elseif ($dishImg !== ''): ?>
            <?php if ($dishWebp !== ''): ?>
            <picture>
              <source srcset="<?= htmlspecialchars($dishWebp) ?>?v=<?= @filemtime(dirname(__DIR__) . '/admin/skeleton/' . $dishWebp) ?: '1' ?>" type="image/webp">
              <img src="<?= htmlspecialchars($dishImg) ?>?v=<?= @filemtime(dirname(__DIR__) . '/admin/skeleton/' . $dishImg) ?: '1' ?>" alt="<?= htmlspecialchars($it['name']) ?>" <?= $isEager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" width="400" height="250">
            </picture>
            <?php else: ?>
            <img src="<?= htmlspecialchars($dishImg) ?>?v=<?= @filemtime(dirname(__DIR__) . '/admin/skeleton/' . $dishImg) ?: '1' ?>" alt="<?= htmlspecialchars($it['name']) ?>" <?= $isEager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" width="400" height="250">
            <?php endif; ?>
          <?php else: ?>
            <?php if ($dishVideo !== ''): ?>
            <video src="<?= htmlspecialchars($dishVideo) ?>" muted playsinline preload="none" disablepictureinpicture aria-hidden="true" tabindex="-1" style="<?= $coverStyle ?>"></video>
            <?php else: ?>
            <img alt="<?= htmlspecialchars($it['name']) ?>" <?= $isEager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" width="400" height="250" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==">
            <?php endif; ?>
          <?php endif; ?>
        </div>
        <div class="dish-body">
          <div class="dish-top">
            <h3><?= htmlspecialchars($it['name']) ?></h3>
            <div class="price"><?= number_format($it['price'], 2, ',', ' ') ?> <?= $restaurant['currency'] ?></div>
          </div>
          <p class="dish-desc"><?= htmlspecialchars($it['desc']) ?></p>
          <div class="dish-foot">
            <span class="weight"><?= htmlspecialchars($it['weight']) ?></span>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>
</main>

<?php if($PV['footer']): ?>
<footer class="footer">
  <div class="wrap footer-inner">
    <p class="footer-note">© 2026 <?= htmlspecialchars($restaurant['name']) ?>. Bütün hüquqlar qorunur.</p>
  </div>
</footer>
<?php endif; ?>

<!-- Waiter modal -->
<div class="overlay" id="waiterOverlay" hidden>
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="waiterTitle">
    <button class="modal-x" id="waiterClose" type="button" aria-label="Bağla">✕</button>
    <h3 id="waiterTitle">Ofisiant çağır</h3>
    <p class="modal-sub">Masa nömrənizi seçin — çağırış birbaşa mətbəx ekranına düşür.</p>
    <label class="fld">
      <span>Masa nömrəsi</span>
      <select id="tableNo">
        <?php for($i=1;$i<=20;$i++): ?>
          <option value="<?= $i ?>" <?= (string)$masa === (string)$i ? 'selected' : '' ?>>Masa <?= $i ?></option>
        <?php endfor; ?>
      </select>
    </label>
    <label class="fld">
      <span>Qeyd (istəyə bağlı)</span>
      <input type="text" id="waiterNote" placeholder="Məs. hesab zəhmət olmasa" maxlength="120">
    </label>
    <button class="btn-primary" id="waiterSend" type="button">Çağırışı göndər</button>
    <p class="modal-ok" id="waiterOk" hidden>✓ Çağırış göndərildi. Ofisiant yaxınlaşır.</p>
    <p class="modal-err" id="waiterErr" hidden>Xəta oldu, zəhmət olmasa yenidən cəhd edin.</p>
  </div>
</div>

<!-- Wi-Fi QR modal: kamera ilə oxut → telefon bir toxunuşla qoşulmağı təklif edir -->
<div class="overlay" id="wifiOverlay" hidden>
  <div class="modal wifi-modal" role="dialog" aria-modal="true" aria-labelledby="wifiTitle">
    <button class="modal-x" id="wifiClose" type="button" aria-label="Bağla">✕</button>
    <h3 id="wifiTitle">Wi-Fi-yə qoşul</h3>
    <p class="modal-sub">Telefonun kamerası ilə QR-u oxut — qoşulma avtomatik təklif olunacaq.</p>
    <div class="wifi-qr"><img id="wifiQrImg" alt="Wi-Fi QR kodu" width="200" height="200"></div>
    <p class="wifi-net"><span id="wifiSsid"></span></p>
    <button class="btn-ghost wifi-copy" id="wifiCopy" type="button">Şifrəni köçür</button>
    <p class="modal-ok" id="wifiCopied" hidden>✓ Şifrə köçürüldü.</p>
  </div>
</div>

<!-- Dish detail modal (şəkil + tərkibi + qiymət) -->
<div class="overlay" id="dishOverlay" hidden>
  <div class="modal dish-modal" id="dishModal" role="dialog" aria-modal="true" aria-labelledby="dishName">
    <button class="dish-modal-x" id="dishModalX" type="button" aria-label="Bağla"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
    <div class="dish-modal-video" id="dishVideoWrap" hidden>
      <video id="dishVideo" muted playsinline preload="auto"></video>
    </div>
    <div class="dish-details" id="dishDetails">
    <button class="dish-details-toggle" id="dishDetailsToggle" type="button" aria-label="Tərkibə bax" aria-expanded="false"><span class="ddt-arr" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span><span class="ddt-label">Tərkibə bax</span></button>
    <div class="dish-modal-photo" id="dishPhotoWrap"><img id="dishImg" alt="" decoding="async" width="800" height="500"></div>
    <div class="dish-title-row">
      <div class="dish-title-main">
        <h3 id="dishName"></h3>
        <p class="modal-sub" id="dishWeight"></p>
      </div>
      <div class="dish-price-pill"><span class="price lg" id="dishPrice"></span></div>
    </div>
    <p class="dish-modal-desc" id="dishDesc"></p>
    <p class="dish-modal-ing-title">Tərkibi</p>
    <div class="dish-modal-ing" id="dishIng"></div>
    <div class="dish-actions">
      <button class="dish-act rate" id="dishRateBtn" type="button" aria-label="Yeməyi qiymətləndir">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5l2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.5l1.2-6.5L2.5 9.4l6.6-.9z"/></svg>
        <span>Qiymət ver</span>
      </button>
      <button class="dish-act report" id="dishReportBtn" type="button" aria-label="Yeməkdən şikayət et">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
        <span>Şikayət et</span>
      </button>
    </div>
    <div class="dish-feedback" id="dishRateForm" hidden>
      <p class="fb-title">Yeməyi qiymətləndir</p>
      <div class="fb-stars" id="dishStars" role="radiogroup" aria-label="Ulduz seç">
        <button type="button" data-v="1" aria-label="1 ulduz">★</button>
        <button type="button" data-v="2" aria-label="2 ulduz">★</button>
        <button type="button" data-v="3" aria-label="3 ulduz">★</button>
        <button type="button" data-v="4" aria-label="4 ulduz">★</button>
        <button type="button" data-v="5" aria-label="5 ulduz">★</button>
      </div>
      <button class="btn-primary" id="dishRateSend" type="button">Göndər</button>
      <p class="modal-ok" id="dishRateOk" hidden>✓ Səsiniz qeydə alındı. Təşəkkür edirik!</p>
      <p class="modal-err" id="dishRateErr" hidden></p>
    </div>
    <div class="dish-feedback" id="dishReportForm" hidden>
      <p class="fb-title">Problemi bildirin</p>
      <div class="fb-reasons" id="dishReasons">
        <button type="button" data-r="Dad">Dad</button>
        <button type="button" data-r="Keyfiyyət">Keyfiyyət</button>
        <button type="button" data-r="Porsiya">Porsiya</button>
        <button type="button" data-r="Təmizlik">Təmizlik</button>
        <button type="button" data-r="Digər">Digər</button>
      </div>
      <input type="text" id="dishReportText" placeholder="Qısa şərh (istəyə bağlı)" maxlength="200" autocomplete="off">
      <button class="btn-primary" id="dishReportSend" type="button">Şikayəti göndər</button>
      <p class="modal-ok" id="dishReportOk" hidden>✓ Şikayətiniz qəbul edildi. Üzr istəyirik!</p>
      <p class="modal-err" id="dishReportErr" hidden></p>
    </div>
    </div>
    <button class="dish-details-back" id="dishDetailsBack" type="button" aria-label="Tərkibi bağla"><span class="ddt-arr ddt-up" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg></span><span class="ddt-label">Tərkibi bağla</span></button>
  </div>
</div>

<script>
window.__TR = <?= json_encode(json_decode(@file_get_contents(dirname(__DIR__) . '/admin/skeleton/data/lang.json'), true) ?: [], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/app.js?v=<?= @filemtime(dirname(__DIR__) . '/admin/skeleton/assets/js/app.js') ?: '4.7' ?>" defer></script>
<script src="assets/js/lang.js?v=<?= @filemtime(dirname(__DIR__) . '/admin/skeleton/assets/js/lang.js') ?: '1.0' ?>" defer></script>
</body>
</html>

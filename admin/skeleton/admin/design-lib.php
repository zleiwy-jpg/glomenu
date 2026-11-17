<?php
// gloMenu — dizayn palitra kitabxanası (tək mənbə).
// Həm vitrin (index.php), həm admin Dizayn səhifəsi buradan istifadə edir.
// Çıxış vermir, BOM-suz saxla.

function design_themes(): array
{
    return [
        1 => ['paper' => '#000000', 'card' => '#1C1915', 'card2' => '#242019', 'line' => '#2C2620', 'lineSoft' => '#231F19', 'accent' => '#B91C1C', 'accentDark' => '#7F1D1D', 'dark' => '#12100D', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
        2 => ['paper' => '#04070F', 'card' => '#0B1220', 'card2' => '#101A2E', 'line' => '#1B2A45', 'lineSoft' => '#141E33', 'accent' => '#1D4ED8', 'accentDark' => '#1E3A8A', 'dark' => '#070C16', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
        3 => ['paper' => '#0A0614', 'card' => '#150E24', 'card2' => '#1C1330', 'line' => '#2C2145', 'lineSoft' => '#211A36', 'accent' => '#7C3AED', 'accentDark' => '#5B21B6', 'dark' => '#0D0918', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
        4 => ['paper' => '#04100C', 'card' => '#0A1A14', 'card2' => '#0E211A', 'line' => '#1A3329', 'lineSoft' => '#142820', 'accent' => '#059669', 'accentDark' => '#065F46', 'dark' => '#07120E', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
        5 => ['paper' => '#0D0A04', 'card' => '#161006', 'card2' => '#1E1609', 'line' => '#33290F', 'lineSoft' => '#251F0C', 'accent' => '#D9A419', 'accentDark' => '#8A6700', 'dark' => '#0A0803', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
        6 => ['paper' => '#030D12', 'card' => '#08141B', 'card2' => '#0B1B24', 'line' => '#16303C', 'lineSoft' => '#10242E', 'accent' => '#06B6D4', 'accentDark' => '#0E7490', 'dark' => '#02090D', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
        7 => ['paper' => '#12060D', 'card' => '#1D0B14', 'card2' => '#26101B', 'line' => '#3D1E2C', 'lineSoft' => '#2E1622', 'accent' => '#EC4899', 'accentDark' => '#BE185D', 'dark' => '#0D0409', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
        8 => ['paper' => '#100803', 'card' => '#1A0E06', 'card2' => '#231208', 'line' => '#3A2210', 'lineSoft' => '#2C1A0C', 'accent' => '#EA580C', 'accentDark' => '#9A3412', 'dark' => '#0C0602', 'ink' => '#F2EBE0', 'muted' => '#A89E90', 'muted2' => '#6E665C'],
    ];
}

function design_theme_names(): array
{
    return [1 => 'Klassik qırmızı', 2 => 'Gecə göyü', 3 => 'Bənövşəyi', 4 => 'Zümrüd', 5 => 'Qızılı', 6 => 'Firuzə', 7 => 'Çəhrayı', 8 => 'Narıncı'];
}

function design_hex_ok($s): bool
{
    return is_string($s) && (bool)preg_match('/^#[0-9a-fA-F]{6}$/', $s);
}

function design_hex_mix(string $a, string $b, float $w): string
{
    $a = ltrim($a, '#');
    $b = ltrim($b, '#');
    if (strlen($a) === 3) $a = $a[0] . $a[0] . $a[1] . $a[1] . $a[2] . $a[2];
    if (strlen($b) === 3) $b = $b[0] . $b[0] . $b[1] . $b[1] . $b[2] . $b[2];
    $o = '';
    for ($i = 0; $i < 3; $i++) {
        $v = round(hexdec(substr($a, $i * 2, 2)) * $w + hexdec(substr($b, $i * 2, 2)) * (1 - $w));
        $o .= sprintf('%02X', max(0, min(255, (int)$v)));
    }
    return '#' . $o;
}

function design_hex_lum(string $hex): float
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    $c = [hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255];
    foreach ($c as $k => $v) $c[$k] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

/** Baza + fərdi rənglərdən tam palitra. */
function design_theme_palette(array $base, string $bg, string $ac): array
{
    $p = $base;
    if (design_hex_ok($bg)) {
        $p['paper'] = $bg;
        $p['card'] = design_hex_mix($bg, '#FFFFFF', 0.93);
        $p['card2'] = design_hex_mix($bg, '#FFFFFF', 0.89);
        $p['line'] = design_hex_mix($bg, '#FFFFFF', 0.82);
        $p['lineSoft'] = design_hex_mix($bg, '#FFFFFF', 0.90);
        $p['dark'] = design_hex_mix($bg, '#000000', 0.65);
        if (design_hex_lum($bg) > 0.45) {
            $p['ink'] = '#14181D';
            $p['muted'] = '#4B5563';
            $p['muted2'] = '#6B7280';
        } else {
            $p['ink'] = '#F2EBE0';
            $p['muted'] = '#A89E90';
            $p['muted2'] = '#6E665C';
        }
    }
    if (design_hex_ok($ac)) {
        $p['accent'] = $ac;
        $p['accentDark'] = design_hex_mix($ac, '#000000', 0.65);
    }
    return $p;
}

/** Tam fərdi palitra açarları (c_*). Hamısı keçərlidirsə true. */
function design_full_keys(): array
{
    return ['c_paper', 'c_card', 'c_card2', 'c_line', 'c_lineSoft', 'c_accent', 'c_accentDark', 'c_dark', 'c_ink', 'c_muted', 'c_muted2'];
}

/**
 * design.json → 13 CSS dəyişəni.
 * custom=1 + bütün c_* keçərlidirsə birbaşa işlədilir,
 * əks halda köhnə yol (tema + custom_bg/custom_accent).
 */
function design_resolve(array $ds): array
{
    $themes = design_themes();
    $t = isset($ds['theme']) ? (int)$ds['theme'] : 1;
    $base = $themes[$t] ?? $themes[1];
    if (!empty($ds['custom'])) {
        $full = true;
        foreach (design_full_keys() as $k) {
            if (!design_hex_ok($ds[$k] ?? '')) {
                $full = false;
                break;
            }
        }
        if ($full) {
            $p = [
                'paper' => $ds['c_paper'], 'card' => $ds['c_card'], 'card2' => $ds['c_card2'],
                'line' => $ds['c_line'], 'lineSoft' => $ds['c_lineSoft'],
                'accent' => $ds['c_accent'], 'accentDark' => $ds['c_accentDark'], 'dark' => $ds['c_dark'],
                'ink' => $ds['c_ink'], 'muted' => $ds['c_muted'], 'muted2' => $ds['c_muted2'],
            ];
        } else {
            $p = design_theme_palette($base, (string)($ds['custom_bg'] ?? ''), (string)($ds['custom_accent'] ?? ''));
        }
    } else {
        $p = $base;
    }
    // Kateqoriya/ikon fonu custom bayrağından asılı deyil (köhnə davranış)
    $p['catbg'] = design_hex_ok($ds['custom_catbg'] ?? '') ? $ds['custom_catbg'] : 'transparent';
    $p['qiconbg'] = design_hex_ok($ds['custom_qicon'] ?? '') ? $ds['custom_qicon'] : '#0C0B09';
    return $p;
}

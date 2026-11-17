<?php
require __DIR__ . '/config.php';
require __DIR__ . '/lib.php';
require_login();

$slug = (string)($_GET['r'] ?? ($_POST['r'] ?? ''));
$r = $slug !== '' ? find_restaurant($slug) : null;
if ($slug === '' || $r === null || !is_dir(GLOMENU_ROOT . '/' . $slug)) {
    flash('Restoran tapılmadı.', 'err');
    header('Location: menu.php');
    exit;
}

$menu = rest_load_menu($slug);
$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$item = $id ? dish_find($menu['items'], $id) : null;
if ($id && !$item) {
    flash('Məhsul tapılmadı.', 'err');
    header('Location: menu.php?r=' . rawurlencode($slug));
    exit;
}
$isNew = !$item;
$item = $item ?? ['id' => 0, 'cat' => '', 'name' => '', 'desc' => '', 'weight' => '', 'price' => '', 'img' => '', 'video' => '', 'poster' => '', 'coverX' => 50, 'coverY' => 50, 'coverZoom' => 100, 'border' => '', 'tags' => []];

$errors = [];
$defCX = isset($item['coverX']) ? dish_cover_xy($item['coverX']) : 50;
if (isset($item['coverY'])) $defCY = dish_cover_xy($item['coverY']);
elseif (isset($item['coverPos'])) $defCY = dish_cover_xy($item['coverPos']);
else $defCY = 50;
$defCZ = isset($item['coverZoom']) ? dish_cover_zoom($item['coverZoom']) : 100;
$val = [
    'cat' => $_POST['cat'] ?? $item['cat'] ?? '',
    'name' => $_POST['name'] ?? $item['name'] ?? '',
    'desc' => $_POST['desc'] ?? $item['desc'] ?? '',
    'weight' => $_POST['weight'] ?? $item['weight'] ?? '',
    'price' => $_POST['price'] ?? (string)($item['price'] ?? ''),
    'border' => $_POST['border'] ?? $item['border'] ?? '',
    'coverX' => $_POST['coverX'] ?? (string)$defCX,
    'coverY' => $_POST['coverY'] ?? (string)$defCY,
    'coverZoom' => $_POST['coverZoom'] ?? (string)$defCZ,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && empty($_POST) && empty($_FILES)) {
        $errors[] = 'Fayl server limitini keçir (maks 35MB video / 8MB şəkil). Daha kiçik fayl seçin.';
    } else {
    csrf_check();
    $val['cat'] = trim((string)($_POST['cat'] ?? ''));
    $val['name'] = trim((string)($_POST['name'] ?? ''));
    $val['desc'] = trim((string)($_POST['desc'] ?? ''));
    $val['weight'] = trim((string)($_POST['weight'] ?? ''));
    $val['price'] = str_replace(',', '.', trim((string)($_POST['price'] ?? '')));
    $val['border'] = strtoupper(trim((string)($_POST['border'] ?? '')));
    if ($val['border'] !== '' && !preg_match('/^#[0-9A-F]{6}$/', $val['border'])) {
        $errors[] = 'Haşiyə rəngi düzgün deyil (məs. #B91C1C, boş da ola bilər).';
    }
    $coverX = dish_cover_xy($_POST['coverX'] ?? 50);
    $coverY = dish_cover_xy($_POST['coverY'] ?? 50);
    $coverZoom = dish_cover_zoom($_POST['coverZoom'] ?? 100);
    $val['coverX'] = (string)$coverX;
    $val['coverY'] = (string)$coverY;
    $val['coverZoom'] = (string)$coverZoom;

    $catIds = array_column($menu['categories'], 'id');
    if ($val['name'] === '') $errors[] = 'Ad boş ola bilməz.';
    if (!in_array($val['cat'], $catIds, true)) $errors[] = 'Kateqoriya seçin.';
    if ($val['price'] === '' || !is_numeric($val['price']) || (float)$val['price'] < 0) {
        $errors[] = 'Qiymət düzgün deyil.';
    }

    $newImg = $item['img'] ?? '';
    $upImg = dish_upload($slug);
    if (!$upImg['ok']) {
        $errors[] = $upImg['error'];
    } elseif ($upImg['rel'] !== null) {
        $newImg = $upImg['rel'];
    } elseif (!empty($_POST['removeimg']) && !$isNew) {
        $newImg = '';
    }

    $newVideo = $item['video'] ?? '';
    $videoReplaced = false;
    $upVid = dish_upload_video($slug);
    if (!$upVid['ok']) {
        $errors[] = $upVid['error'];
    } elseif ($upVid['rel'] !== null) {
        $newVideo = $upVid['rel'];
        $videoReplaced = true;
    } elseif (!empty($_POST['removevid']) && !$isNew) {
        $newVideo = '';
    }
    $newPoster = $item['poster'] ?? '';
    $upPoster = dish_upload_poster($slug);
    // Poster xətası save-i bloklamır — kartda video fallback ilə görünür.
    if ($upPoster['ok'] && $upPoster['rel'] !== null) {
        $newPoster = $upPoster['rel'];
    } elseif (!empty($_POST['removeposter']) && !$isNew) {
        $newPoster = '';
    }
    if ($newVideo === '') {
        if ($newPoster !== '' && empty($_POST['removeposter']) && ($upPoster['rel'] ?? null) === null) $newPoster = '';
    } elseif ($videoReplaced && ($upPoster['rel'] ?? null) === null) {
        $newPoster = '';
    }
    if ($isNew && $newImg === '' && $newVideo === '') $errors[] = 'Yeni məhsul üçün şəkil və ya video seçin.';

    if (!$errors) {
        $oldImg = $item['img'] ?? '';
        $oldVideo = $item['video'] ?? '';
        $oldPoster = $item['poster'] ?? '';
        $saveId = $isNew ? dish_next_id($menu['items']) : (int)$item['id'];
        $newItem = [
            'id' => $saveId,
            'cat' => $val['cat'],
            'name' => $val['name'],
            'desc' => $val['desc'],
            'weight' => $val['weight'],
            'price' => round((float)$val['price'], 2),
            'img' => $newImg,
            'video' => $newVideo,
            'poster' => $newPoster,
            'coverX' => $coverX,
            'coverY' => $coverY,
            'coverZoom' => $coverZoom,
            'coverPos' => $coverY,
            'border' => $val['border'],
            'tags' => $item['tags'] ?? [],
        ];
        $items = [];
        $found = false;
        foreach ($menu['items'] as $it) {
            if ((int)($it['id'] ?? 0) === $saveId) {
                $items[] = $newItem;
                $found = true;
            } else {
                $items[] = $it;
            }
        }
        if (!$found) $items[] = $newItem;
        $menu['items'] = $items;
        if ($oldImg !== '' && $oldImg !== $newImg) dish_delete_media($slug, $menu['items'], $oldImg);
        if ($oldVideo !== '' && $oldVideo !== $newVideo) dish_delete_media($slug, $menu['items'], $oldVideo);
        if ($oldPoster !== '' && $oldPoster !== $newPoster) dish_delete_media($slug, $menu['items'], $oldPoster);
        rest_save_menu($slug, $menu);
        flash($isNew ? 'Məhsul əlavə edildi.' : 'Dəyişiklik yadda saxlanıldı.');
        header('Location: menu.php?r=' . rawurlencode($slug));
        exit;
    }
    if (!empty($newImg) && $newImg !== ($item['img'] ?? '')) @unlink(GLOMENU_ROOT . '/' . $slug . '/' . $newImg);
    if (!empty($newVideo) && $newVideo !== ($item['video'] ?? '')) @unlink(GLOMENU_ROOT . '/' . $slug . '/' . $newVideo);
    if (!empty($newPoster) && $newPoster !== ($item['poster'] ?? '')) @unlink(GLOMENU_ROOT . '/' . $slug . '/' . $newPoster);
    } // POST else bağla
}

$back = 'menu.php?r=' . rawurlencode($slug);
$body = '<p class="adm-muted" style="margin-bottom:16px">Restoran: <b style="color:var(--ink)">“' . esc($r['name'] ?? $slug) . '”</b></p>';
foreach ($errors as $e) $body .= '<div class="adm-flash err">' . esc($e) . '</div>';

$body .= '<form class="adm-card" method="post" action="product.php?r=' . rawurlencode($slug) . ($isNew ? '' : '&id=' . (int)$item['id']) . '" enctype="multipart/form-data">';
$body .= '<input type="hidden" name="csrf" value="' . esc(csrf_token()) . '">';
$body .= '<input type="hidden" name="r" value="' . esc($slug) . '">';
if (!$isNew) $body .= '<input type="hidden" name="id" value="' . (int)$item['id'] . '">';
$body .= '<div class="adm-grid">';
$body .= '<label class="adm-fld"><span>Ad *</span><input type="text" name="name" value="' . esc($val['name']) . '" required maxlength="80"></label>';
$body .= '<label class="adm-fld"><span>Kateqoriya *</span><select name="cat" required><option value="">— seçin —</option>';
foreach ($menu['categories'] as $c) {
    $body .= '<option value="' . esc($c['id']) . '"' . ($val['cat'] === $c['id'] ? ' selected' : '') . '>' . esc($c['name']) . '</option>';
}
$body .= '</select></label>';
$body .= '</div>';
$body .= '<div class="adm-grid">';
$body .= '<label class="adm-fld"><span>Çəki</span><input type="text" name="weight" value="' . esc($val['weight']) . '" maxlength="30"></label>';
$body .= '<label class="adm-fld"><span>Qiymət (₼) *</span><input type="number" name="price" value="' . esc($val['price']) . '" min="0" step="0.01" required></label>';
$body .= '</div>';
$body .= '<label class="adm-fld"><span>Təsvir</span><textarea name="desc" maxlength="500" rows="3">' . esc($val['desc']) . '</textarea></label>';
$body .= '<div class="adm-fld"><span>Şəkil (video yoxdursa məcburi, video varsa boş buraxın)</span>';
if (!empty($item['img'])) {
    $body .= '<div class="adm-imgprev"><img src="../' . rawurlencode($slug) . '/' . esc($item['img']) . '" alt=""><label class="adm-check"><input type="checkbox" name="removeimg" value="1"> Şəkli sil</label></div>';
}
$body .= '<input type="file" name="imgfile" accept=".jpg,.jpeg,.png,.webp">';
$body .= '<p class="adm-muted" style="margin-top:6px">Video varsa şəkil yükləməyin — kartda və modalda videonun ilk kadrı görünəcək. Video yoxdursa şəkil məcburidir.</p></div>';
$body .= '<div class="adm-fld"><span>Video (MP4/WebM, maks 35MB) — orijinal keyfiyyət və FPS ilə yüklənir, sıxışdırılmır</span>';
if (!empty($item['video'])) {
    $body .= '<div class="adm-imgprev"><video src="../' . rawurlencode($slug) . '/' . esc($item['video']) . '" muted playsinline preload="metadata" style="max-width:220px;border-radius:8px;background:#000"></video><label class="adm-check"><input type="checkbox" name="removevid" value="1"> Videonu sil</label></div>';
}
$body .= '<input type="file" name="vidfile" accept=".mp4,.webm,video/mp4,video/webm">';
$body .= '<p class="adm-muted" style="margin-top:6px">Video olduğu kimi — orijinal keyfiyyət və FPS ilə — yüklənir, sıxışdırılmır.</p>';
$body .= '<p class="adm-muted" style="margin-top:4px">Modalda səssiz oynayır. Kartda və modal açılanda videonun <b>ilk kadrı</b> poster kimi görünür (aşağıda avtomatik tutulur).</p></div>';
$existingVideoUrl = !empty($item['video']) ? '../' . rawurlencode($slug) . '/' . $item['video'] : '';
$existingPosterUrl = !empty($item['poster']) ? '../' . rawurlencode($slug) . '/' . $item['poster'] : '';
if ($existingPosterUrl === '' && !empty($item['video'])) {
    $legacyP = preg_replace('/\.(mp4|webm)$/i', '-poster.jpg', (string)$item['video']);
    if ($legacyP !== $item['video'] && @is_file(GLOMENU_ROOT . '/' . $slug . '/' . $legacyP)) $existingPosterUrl = '../' . rawurlencode($slug) . '/' . $legacyP;
}
$body .= '<div class="adm-fld" id="posterFld"><span>Poster — kartda (dish-photo has-video) və modalda görünən ilk kadr + crop (16:10)</span>';
$body .= '<div id="posterCropBox" style="border:1px solid var(--line);border-radius:12px;padding:14px;background:rgba(255,255,255,.02)">';
$body .= '<div id="posterPreview" title="Sürüşdür — görünən hissəni seç" style="position:relative;width:100%;max-width:420px;aspect-ratio:16/10;overflow:hidden;border-radius:10px;background:#000;cursor:crosshair;touch-action:none;user-select:none;-webkit-user-select:none">';
$body .= '<img id="posterImg" src="' . esc($existingPosterUrl) . '" alt="" draggable="false" style="width:100%;height:100%;object-fit:cover;display:' . ($existingPosterUrl !== '' ? 'block' : 'none') . ';pointer-events:none">';
$body .= '<span id="posterEmpty" style="position:absolute;inset:0;display:' . ($existingPosterUrl !== '' ? 'none' : 'grid') . ';place-items:center;color:var(--muted);font-size:13px;padding:12px;text-align:center">Poster yoxdur — video seçib «İlk kadrı tut»u basın</span>';
$body .= '</div>';
$body .= '<video id="posterVideo" muted playsinline preload="auto" style="display:none"></video>';
$body .= '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">';
$body .= '<button type="button" class="adm-btn sm" id="posterCaptureBtn">◉ İlk kadrı tut</button>';
$body .= '<label class="adm-btn sm" style="cursor:pointer">Fayldan poster seç<input type="file" id="posterManual" accept=".jpg,.jpeg" style="display:none"></label>';
if ($existingPosterUrl !== '') $body .= '<label class="adm-check"><input type="checkbox" name="removeposter" value="1"> Posteri sil</label>';
$body .= '</div>';
$body .= '<input type="file" name="posterfile" id="posterFileInp" accept=".jpg,.jpeg" style="margin-top:10px">';
$body .= '<div id="posterStatus" class="adm-muted" style="margin-top:6px">' . ($existingPosterUrl !== '' ? 'Hazır poster var — sürüşdürüb crop-u dəyişə bilərsiniz.' : 'Video seçiləndə ilk kadr avtomatik tutulacaq.') . '</div>';
$body .= '<div style="display:grid;gap:10px;margin-top:10px;max-width:420px">';
$body .= '<label class="adm-muted" style="display:grid;gap:4px">Üfüqi fokus (X): <b id="coverXVal">' . (int)$val['coverX'] . '%</b><input type="range" id="coverXRange" name="coverX" min="0" max="100" step="1" value="' . (int)$val['coverX'] . '"></label>';
$body .= '<label class="adm-muted" style="display:grid;gap:4px">Şaquli fokus (Y): <b id="coverYVal">' . (int)$val['coverY'] . '%</b><input type="range" id="coverYRange" name="coverY" min="0" max="100" step="1" value="' . (int)$val['coverY'] . '"></label>';
$body .= '<label class="adm-muted" style="display:grid;gap:4px">Zoom: <b id="coverZoomVal">' . (int)$val['coverZoom'] . '%</b><input type="range" id="coverZoomRange" name="coverZoom" min="100" max="250" step="1" value="' . (int)$val['coverZoom'] . '"></label>';
$body .= '</div>';
$body .= '<p class="adm-muted" style="margin-top:8px">Önizləmə kartdakı <b>dish-photo has-video (16:10)</b> ilə eyni CSS-lə göstərilir — nə görürsünüzsə, saytda o görünəcək. Modal açılanda eyni poster videonun posteri olur.</p>';
$body .= '</div></div>';
$body .= '<div class="adm-fld"><span>Haşiyə rəngi (kartın çərçivəsi — boşsa standart)</span>';
$body .= '<span class="ds-hexrow"><input type="color" id="borderPick" value="' . esc($val['border'] !== '' ? $val['border'] : '#B91C1C') . '" style="width:46px;height:38px;padding:2px;border-radius:8px;cursor:pointer">';
$body .= '<input type="text" id="borderHex" name="border" value="' . esc($val['border']) . '" maxlength="7" placeholder="boş = standart" spellcheck="false" style="max-width:130px;font-family:monospace;text-transform:uppercase">';
$body .= '<button type="button" class="adm-btn sm" id="borderClear">Standart</button></span></div>';
$body .= '<script>(function(){var p=document.getElementById("borderPick"),h=document.getElementById("borderHex");if(!p||!h)return;p.addEventListener("input",function(){h.value=p.value.toUpperCase()});h.addEventListener("input",function(){if(/^#[0-9a-fA-F]{6}$/.test(h.value))p.value=h.value});document.getElementById("borderClear").addEventListener("click",function(){h.value=""})})();</script>';
$body .= '<div class="adm-form-btns"><button class="adm-btn primary" type="submit">' . ($isNew ? 'Əlavə et' : 'Yadda saxla') . '</button><a class="adm-btn" href="' . $back . '">Ləğv et</a></div>';
$body .= <<<'PHTML'
<script>(function(){
"use strict";
var form=document.querySelector("form.adm-card");
if(!form)return;
var vidInp=form.querySelector('input[name="vidfile"]');
var posterInp=document.getElementById("posterFileInp");
var posterManual=document.getElementById("posterManual");
var posterImg=document.getElementById("posterImg");
var posterEmpty=document.getElementById("posterEmpty");
var posterPreview=document.getElementById("posterPreview");
var posterVideo=document.getElementById("posterVideo");
var capBtn=document.getElementById("posterCaptureBtn");
var statusEl=document.getElementById("posterStatus");
var xR=document.getElementById("coverXRange"),yR=document.getElementById("coverYRange"),zR=document.getElementById("coverZoomRange");
var xV=document.getElementById("coverXVal"),yV=document.getElementById("coverYVal"),zV=document.getElementById("coverZoomVal");
var rmVid=form.querySelector('input[name="removevid"]');
var submitBtn=form.querySelector('button[type="submit"]');
function setStatus(t){if(statusEl)statusEl.textContent=t||"";}
function curXY(){return {x:xR?+xR.value||50:50,y:yR?+yR.value||50:50,z:zR?+zR.value||100:100};}
function paint(){var c=curXY();if(xV)xV.textContent=c.x+"%";if(yV)yV.textContent=c.y+"%";if(zV)zV.textContent=c.z+"%";if(posterImg){posterImg.style.objectPosition=c.x+"% "+c.y+"%";posterImg.style.transform=c.z>100?("scale("+(c.z/100).toFixed(3)+")"):"";}}
if(xR)xR.addEventListener("input",paint);if(yR)yR.addEventListener("input",paint);if(zR)zR.addEventListener("input",paint);
paint();
function showImg(url){if(!posterImg)return;posterImg.src=url;posterImg.style.display="block";if(posterEmpty)posterEmpty.style.display="none";paint();}
if(posterManual)posterManual.addEventListener("change",function(){var f=posterManual.files&&posterManual.files[0];if(!f)return;try{var dt=new DataTransfer();dt.items.add(f);if(posterInp)posterInp.files=dt.files;}catch(e){setStatus("Brauzer faylı köçürə bilmədi.");return;}showImg(URL.createObjectURL(f));setStatus("Fayldan poster seçildi ("+Math.round(f.size/1024)+" KB).");});
function vidSource(){if(vidInp&&vidInp.files&&vidInp.files[0]){return {isFile:true,file:vidInp.files[0],url:null};}var exVid=null;try{var fld=vidInp?vidInp.closest(".adm-fld"):null;var v=fld?fld.querySelector(".adm-imgprev video"):null;var s=v?v.getAttribute("src"):"";if(s)exVid=s;}catch(e){}if(exVid)return {isFile:false,file:null,url:exVid};return null;}
var autoUrl=null;
function setVideoUrl(url){return new Promise(function(res,rej){try{if(posterVideo.getAttribute("src")===url&&posterVideo.readyState>=1){res();return;}}catch(e){}var done=false;function ok(){if(done)return;done=true;cleanup();res();}function bad(m){if(done)return;done=true;cleanup();rej(new Error(m||"video açılmadı"));}function cleanup(){try{posterVideo.removeEventListener("loadeddata",ok);}catch(e){}try{posterVideo.removeEventListener("loadedmetadata",ok);}catch(e){}try{posterVideo.removeEventListener("error",onErr);}catch(e){}}function onErr(){bad("video oxunmadı");}posterVideo.addEventListener("loadeddata",ok);posterVideo.addEventListener("loadedmetadata",ok);posterVideo.addEventListener("error",onErr);try{posterVideo.muted=true;posterVideo.preload="auto";posterVideo.src=url;}catch(e){bad(String(e));return;}setTimeout(function(){if(!done){try{if(posterVideo.readyState>=1)ok();else bad("vaxt aşımı (video yüklənmədi)");}catch(e){bad(String(e));}}},8000);});}
function grabFrame(){return new Promise(function(res,rej){try{var vw=posterVideo.videoWidth||0,vh=posterVideo.videoHeight||0;if(!vw||!vh||posterVideo.readyState<1){rej(new Error("video hazır deyil"));return;}var doDraw=function(){try{vw=posterVideo.videoWidth||0;vh=posterVideo.videoHeight||0;if(!vw||!vh){rej(new Error("kadr ölçüsü 0"));return;}var maxW=1280,sc=Math.min(1,maxW/vw);var cw=Math.round(vw*sc),ch=Math.round(vh*sc);var cv=document.createElement("canvas");cv.width=cw;cv.height=ch;var cx=cv.getContext("2d");if(!cx){rej(new Error("canvas yoxdur"));return;}try{cx.drawImage(posterVideo,0,0,cw,ch);}catch(e){rej(new Error("kadr oxunmadı"));return;}if(cv.toBlob){cv.toBlob(function(b){if(!b){rej(new Error("JPEG alınmadı"));return;}res({blob:b,w:cw,h:ch});},"image/jpeg",0.85);}else{rej(new Error("toBlob yoxdur"));}}catch(e){rej(e);}};var seekT=0.1;try{if(posterVideo.duration&&isFinite(posterVideo.duration)&&posterVideo.duration>0.05)seekT=Math.min(0.1,posterVideo.duration/3);}catch(e){}var needSeek=true;try{needSeek=Math.abs((posterVideo.currentTime||0)-seekT)>0.05;}catch(e){}if(!needSeek){setTimeout(doDraw,200);return;}var done=false;var timer=null;function onSeek(){if(done)return;done=true;clearTimeout(timer);try{posterVideo.removeEventListener("seeked",onSeek);}catch(e){}setTimeout(doDraw,120);}posterVideo.addEventListener("seeked",onSeek);timer=setTimeout(function(){if(done)return;done=true;try{posterVideo.removeEventListener("seeked",onSeek);}catch(e){}doDraw();},5000);try{posterVideo.currentTime=seekT;}catch(e){if(!done){done=true;clearTimeout(timer);rej(e);}} }catch(e){rej(e);}});}
function captureFrom(src){if(window.__capBusy)return window.__capBusy;var url=src.url;if(src.isFile){if(autoUrl&&autoUrl.indexOf("blob:")===0){try{URL.revokeObjectURL(autoUrl);}catch(e){}}try{url=URL.createObjectURL(src.file);autoUrl=url;}catch(e){return Promise.reject(new Error("fayl oxunmadı"));}}var p=setVideoUrl(url).then(grabFrame);window.__capBusy=p;var clear=function(){if(window.__capBusy===p)window.__capBusy=null;};if(p&&p.then)p.then(clear,clear);else window.__capBusy=null;return p;}
function ensurePoster(){if(rmVid&&rmVid.checked)return Promise.resolve("skipped");var hasNewVid=!!(vidInp&&vidInp.files&&vidInp.files.length);var hasPosterFile=!!(posterInp&&posterInp.files&&posterInp.files.length);var hasExistingImg=!!(posterImg&&posterImg.getAttribute("src"));if(hasPosterFile)return Promise.resolve("exists");if(!hasNewVid&&hasExistingImg)return Promise.resolve("exists");var src=vidSource();if(!src)return Promise.resolve("no-video");setStatus("İlk kadr tutulur…");return captureFrom(src).then(function(r){var f=new File([r.blob],"poster-auto.jpg",{type:"image/jpeg"});try{var dt=new DataTransfer();dt.items.add(f);posterInp.files=dt.files;}catch(e){throw new Error("fayl bağlanmadı");}showImg(URL.createObjectURL(f));setStatus("İlk kadr tutuldu ("+r.w+"×"+r.h+").");return "captured";}).catch(function(err){setStatus("Avtomatik poster alınmadı ("+((err&&err.message)||err)+") — davam etmək olar (kartda video görünəcək).");return "failed";});}
window.__ensurePoster=ensurePoster;
if(capBtn)capBtn.addEventListener("click",function(){ensurePoster();});
if(vidInp)vidInp.addEventListener("change",function(){if(rmVid)rmVid.checked=false;if(posterInp)posterInp.value="";var src=vidSource();if(!src)return;setStatus("Yeni video — ilk kadr tutulur…");captureFrom(src).then(function(r){var f=new File([r.blob],"poster-auto.jpg",{type:"image/jpeg"});try{var dt=new DataTransfer();dt.items.add(f);posterInp.files=dt.files;}catch(e){}showImg(URL.createObjectURL(f));setStatus("İlk kadr tutuldu ("+r.w+"×"+r.h+").");}).catch(function(err){setStatus("Avtomatik poster alınmadı ("+((err&&err.message)||err)+").");});});
(function(){if(!posterPreview)return;var drag=false;function setFromEvent(ev){try{var r=posterPreview.getBoundingClientRect();var px=((ev.clientX-r.left)/r.width)*100,py=((ev.clientY-r.top)/r.height)*100;px=Math.max(0,Math.min(100,Math.round(px)));py=Math.max(0,Math.min(100,Math.round(py)));if(xR)xR.value=px;if(yR)yR.value=py;paint();}catch(e){}}posterPreview.addEventListener("pointerdown",function(ev){drag=true;try{posterPreview.setPointerCapture(ev.pointerId);}catch(e){}setFromEvent(ev);});posterPreview.addEventListener("pointermove",function(ev){if(drag)setFromEvent(ev);});["pointerup","pointercancel","pointerleave"].forEach(function(t){posterPreview.addEventListener(t,function(){drag=false;});});})();
form.addEventListener("submit",function(e){if(form._posterBusy)return;e.preventDefault();form._posterBusy=true;if(submitBtn){submitBtn.disabled=true;submitBtn.textContent="Hazırlanır…";}var done=false;var go=function(){if(done)return;done=true;try{form._posterBusy=false;}catch(_){}form.submit();};var failSafe=setTimeout(go,10000);try{ensurePoster().then(function(){clearTimeout(failSafe);go();},function(){clearTimeout(failSafe);go();});}catch(_){clearTimeout(failSafe);go();}});
})();
</script>
PHTML;
$body .= '</form>';

admin_layout(($isNew ? 'Yeni məhsul' : 'Məhsulu redaktə et') . ' — ' . ($r['name'] ?? $slug), 'menu.php', $body);

<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
license_check();
$slug = $_GET['s'] ?? '';
$link = db_row("SELECT l.*, d.filename, d.original_name, d.user_id FROM links l JOIN documents d ON d.id=l.doc_id WHERE l.slug=?", [$slug]);
if(!$link){ http_response_code(404); exit('Link not found'); }
if((int)$link['allow_view']!==1){ http_response_code(403); exit('Viewing disabled'); }
$token = token_for($link['id'],$link['doc_id']);
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($link['original_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0b1220;color:#eaf1ff}
.toolbar{position:sticky;top:0;z-index:10;padding:.5rem 1rem;background:#101828;border-bottom:1px solid #223;display:flex;gap:.5rem;align-items:center}
.viewer{display:grid;place-items:center;padding:1rem}
.page{position:relative;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 8px 20px #0006}
canvas{display:block;width:100%;height:auto}
#textLayer{position:absolute;inset:0;pointer-events:none}
.watermark{position:absolute;inset:0;display:grid;place-items:center;opacity:.12;pointer-events:none;font-weight:800;font-size:42px;transform:rotate(-18deg);color:#0b1220}
.nodl{opacity:.75}
</style>
</head><body>
<div class="toolbar">
  <button id="prev" class="btn btn-sm btn-light">◀︎</button>
  <button id="next" class="btn btn-sm btn-light">▶︎</button>
  <span>Page <input id="pageNumber" class="form-control form-control-sm" style="width:70px" type="number" min="1" value="1"> / <span id="pageCount">--</span></span>
  <button id="zoomOut" class="btn btn-sm btn-light">−</button>
  <button id="zoomIn" class="btn btn-sm btn-light">+</button>
  <select id="fit" class="form-select form-select-sm" style="width:120px">
    <option value="auto">Auto</option><option value="fitW">Fit width</option><option value="fitH">Fit height</option><option value="actual">100%</option>
  </select>
  <button id="rotate" class="btn btn-sm btn-light">⟳</button>
  <?php if((int)$link['allow_download']===1): ?>
    <a class="btn btn-sm btn-success" id="downloadBtn" href="<?= APP_BASE_URL ?>/api/pdf.php?dl=1&tok=<?= urlencode($token) ?>">Download</a>
  <?php else: ?>
    <button class="btn btn-sm btn-secondary nodl" disabled>Download off</button>
  <?php endif; ?>
</div>

<div class="viewer">
  <div class="page">
    <canvas id="canvas"></canvas>
    <div id="textLayer"></div>
    <div class="watermark">Secure View — <?= htmlspecialchars(parse_url(APP_BASE_URL,PHP_URL_HOST) ?? 'localhost') ?></div>
  </div>
</div>

<script>
  navigator.sendBeacon("<?= APP_BASE_URL ?>/api/analytics.php", new Blob([JSON.stringify({
    s:"<?= $slug ?>", event:"view", ref: document.referrer
  })], {type:"application/json"}));
  document.addEventListener('contextmenu', e => e.preventDefault());
  document.addEventListener('keydown', e => {
    if((e.ctrlKey||e.metaKey) && ['s','p','S','P'].includes(e.key)) e.preventDefault();
  });
</script>

<script type="module" src="assets/viewer.js"></script>
<script>
  window.__PDF_VIEWER_BOOTSTRAP__ = {
    streamUrl: "<?= APP_BASE_URL ?>/api/pdf.php?tok=<?= urlencode($token) ?>",
    allowSearch: <?= (int)$link['allow_search'] ?>,
  };
</script>
</body></html>

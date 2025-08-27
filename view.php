<?php
// ===== view.php (Pro UI — Dark + Polished Light Theme + fixed sidebar collapse) =====
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';
license_check();

$slug = $_GET['s'] ?? '';
$link = db_row(
  "SELECT l.*, d.filename, d.original_name, d.user_id
     FROM links l
     JOIN documents d ON d.id=l.doc_id
    WHERE l.slug=?",
  [$slug]
);
if (!$link) { http_response_code(404); exit('Link not found'); }
if ((int)$link['allow_view'] !== 1) { http_response_code(403); exit('Viewing disabled'); }

$token = token_for($link['id'], $link['doc_id']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= htmlspecialchars($link['original_name']) ?></title>

<!-- DM Sans font -->
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">

<style>
  :root{
    /* Layout */
    --topbar-h: 56px;
    --sidebar-w: 260px;
    --divider-w: 10px;

    /* ====== DARK THEME TOKENS ====== */
    --bg:#111622;
    --panel:#171d2c;
    --panel-2:#1e2540;
    --text:#eef2ff;
    --muted:#9aa3b2;
    --accent:#6aa2ff;
    --accent-weak:#6aa2ff2d;
    --divider:#2c354b;

    --radius:12px;
    --shadow:0 10px 28px rgba(0,0,0,.55);
    --shadow-soft:0 6px 16px rgba(0,0,0,.4);
    --thumb-bg-grad:linear-gradient(180deg,#1a2130,#171d2a);
    --topbar-grad:linear-gradient(180deg,#1a2133 0%, #151b2b 100%);
    --doc-grad:radial-gradient(1400px 700px at 80% -20%, #0e1320 0%, transparent 50%);
    --icon-color:var(--text);
    --watermark:#0b1220;
  }
  body[data-theme="light"]{
    --bg:#F7F9FC;
    --panel:#FFFFFF;
    --panel-2:#FAFBFF;
    --text:#0E1320;
    --muted:#667085;
    --accent:#2B64FF;
    --accent-weak:#2B64FF22;
    --divider:#E6EAF2;

    --shadow:0 10px 24px rgba(16,24,40,.10);
    --shadow-soft:0 6px 14px rgba(16,24,40,.08);
    --thumb-bg-grad:linear-gradient(180deg,#ffffff,#f7f9fe);
    --topbar-grad:linear-gradient(180deg,#ffffff 0%, #F4F7FB 100%);
    --doc-grad:radial-gradient(1200px 600px at 60% -15%, #EAF1FF 0%, transparent 55%);
    --icon-color:#0E1320;
    --watermark:#5E6D8A;
    background:#F7F9FC;
  }

  html,body{height:100%}
  body{
    margin:0; overflow:hidden;
    background: radial-gradient(1200px 600px at 10% -15%, #19213a 0%, transparent 55%), var(--bg);
    color:var(--text);
    font:14px/1.5 "DM Sans", system-ui, -apple-system, Segoe UI, Roboto, Arial;
    -webkit-font-smoothing:antialiased; text-rendering:optimizeLegibility;
  }

  /* ===== Topbar ===== */
  .topbar{
    position:sticky; top:0; z-index:50;
    height:var(--topbar-h);
    display:flex; align-items:center; gap:.75rem;
    padding:0 14px;
    background:var(--topbar-grad);
    border-bottom:1px solid color-mix(in oklab, var(--divider), #000 8%);
    box-shadow:var(--shadow-soft);
  }
  .iconbtn{
    width:36px;height:36px; display:grid; place-items:center;
    border-radius:10px; color:var(--icon-color);
    background:linear-gradient(180deg, color-mix(in oklab, var(--panel) 88%, #000 8%), color-mix(in oklab, var(--panel) 72%, #000 10%));
    border:1px solid color-mix(in oklab, var(--divider), #000 10%);
    cursor:pointer; user-select:none;
    transition:filter .15s ease, transform .12s, box-shadow .15s;
    box-shadow:0 1px 0 rgba(255,255,255,.06) inset;
  }
  body[data-theme="light"] .iconbtn{
    background:linear-gradient(180deg,#ffffff,#F4F7FF);
    border-color:#DFE5F0;
    box-shadow:0 1px 0 rgba(255,255,255,.8) inset;
  }
  .iconbtn:hover{ filter:brightness(1.05); transform:translateY(-1px); box-shadow:0 6px 12px rgba(0,0,0,.12); }
  .seg{
    display:flex; gap:6px; padding:4px; border-radius:12px;
    background:linear-gradient(180deg,color-mix(in oklab, var(--panel) 86%, #000 8%), color-mix(in oklab, var(--panel) 76%, #000 10%));
    border:1px solid color-mix(in oklab, var(--divider), #000 10%);
  }
  body[data-theme="light"] .seg{
    background:linear-gradient(180deg,#ffffff,#F4F7FF);
    border-color:#DFE5F0;
  }
  .seg .iconbtn{ width:32px;height:32px;border-radius:8px }

  .zoom{
    height:36px;border-radius:10px;padding:0 12px; cursor:pointer;
    color:var(--icon-color);
    background:linear-gradient(180deg,color-mix(in oklab, var(--panel) 86%, #000 8%), color-mix(in oklab, var(--panel) 76%, #000 10%));
    border:1px solid color-mix(in oklab, var(--divider), #000 10%); min-width:110px;
    appearance:none;
  }
  body[data-theme="light"] .zoom{ background:linear-gradient(180deg,#ffffff,#F4F7FF); border-color:#DFE5F0; }
  .spacer{flex:1}

  /* Settings dropdown */
  .settings{ position:relative }
  .dropdown{
    position:absolute; right:0; top:42px; min-width:180px;
    background:linear-gradient(180deg, color-mix(in oklab, var(--panel) 98%, #000 2%), color-mix(in oklab, var(--panel) 94%, #000 5%));
    border:1px solid var(--divider); border-radius:12px; padding:6px;
    box-shadow:0 16px 36px rgba(16,24,40,.20);
    display:none;
  }
  body[data-theme="light"] .dropdown{ background:#fff; border-color:#E6EAF2; }
  .dropdown.show{ display:block }
  .dropdown .item{
    width:100%; text-align:left; background:transparent; border:0; color:var(--text);
    padding:10px 12px; border-radius:10px; cursor:pointer; transition:.15s ease;
  }
  .dropdown .item:hover{ background:color-mix(in oklab, var(--accent) 12%, transparent) }

  /* ===== App shell + collapsed behavior ===== */
  .shell{
    display:grid;
    grid-template-columns: var(--sidebar-w) var(--divider-w) 1fr;
    height:calc(100vh - var(--topbar-h));
    transition:grid-template-columns .22s ease;
  }
  .vbar{ background:var(--divider) }

  /* keep sidebar in the flow; don't use display:none to avoid render glitches */
  #sidebar{ width:var(--sidebar-w); transition: width .22s ease, visibility .22s ease; }
  .shell.collapsed { grid-template-columns: 0 var(--divider-w) 1fr; }
  .shell.collapsed #sidebar{
    width:0 !important; visibility:hidden; pointer-events:none;
  }

  /* ===== Sidebar (sticky tabs, internal scroll) ===== */
  .sidebar{
    background:var(--panel);
    border-right:1px solid var(--divider);
    display:grid; grid-template-rows:auto 1fr;
    overflow:hidden;
    box-shadow: inset -1px 0 0 rgba(0,0,0,.05);
  }
  .tabs.sticky{
    position:sticky; top:0; z-index:5;
    padding:10px 14px;
    background:linear-gradient(180deg, color-mix(in oklab, var(--panel) 98%, #000 2%), color-mix(in oklab, var(--panel) 92%, #000 8%));
    border-bottom:1px solid var(--divider);
  }
  .seg2{
    width:210px; display:flex; gap:10px; align-items:center; padding:6px;
    border-radius:14px;
    background:linear-gradient(180deg,color-mix(in oklab, var(--panel) 86%, #000 10%), color-mix(in oklab, var(--panel) 76%, #000 12%));
    border:1px solid color-mix(in oklab, var(--divider), #000 10%); box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
  }
  body[data-theme="light"] .seg2{
    background:linear-gradient(180deg,#ffffff,#F4F7FF);
    border-color:#DFE5F0; box-shadow: inset 0 1px 0 rgba(255,255,255,.85);
  }
  .tab{
    flex:1 0 0; height:36px; display:grid; place-items:center;
    border-radius:10px; border:1px solid transparent;
    color:var(--icon-color); background:transparent; cursor:pointer;
    transition: background .15s, border-color .15s, box-shadow .15s, transform .12s;
  }
  .tab:hover{ background:color-mix(in oklab, var(--accent) 10%, transparent); transform: translateY(-1px); }
  .tab.active{
    background:linear-gradient(180deg,color-mix(in oklab, var(--accent) 25%, transparent), color-mix(in oklab, var(--accent) 14%, transparent));
    border-color: var(--accent);
    box-shadow: 0 0 0 1px var(--accent) inset, 0 6px 14px rgba(0,0,0,.25);
  }

  .panel{
    background:var(--panel-2);
    padding:16px 12px 24px;
    overflow:auto;
  }
  .panel::-webkit-scrollbar{ width:12px }
  .panel::-webkit-scrollbar-track{ background:color-mix(in oklab, var(--panel-2) 90%, #000 10%); border-radius:10px }
  .panel::-webkit-scrollbar-thumb{
    background:color-mix(in oklab, var(--divider) 60%, #000 10%); border-radius:10px;
    border:2px solid color-mix(in oklab, var(--panel-2) 92%, #000 8%);
  }
  body[data-theme="light"] .panel::-webkit-scrollbar-track{ background:#EEF2F8 }
  body[data-theme="light"] .panel::-webkit-scrollbar-thumb{ background:#CBD5E1; border-color:#EEF2F8 }

  /* Thumbnails */
  .thumb{
    border:1px solid var(--divider); border-radius:14px;
    background:var(--thumb-bg-grad);
    padding:14px; margin:10px 0 22px; cursor:pointer;
    box-shadow:var(--shadow);
    transition:border-color .15s, transform .15s, box-shadow .15s, background .15s;
    text-align:center;
  }
  .thumb:hover{ border-color:color-mix(in oklab, var(--accent) 45%, var(--divider)); transform: translateY(-1px); }
  .thumb canvas{ width:100%; height:auto; border-radius:10px; display:block }
  .thumb.active{
    border-color:var(--accent);
    box-shadow:0 0 0 2px var(--accent-weak) inset, 0 12px 28px rgba(16,24,40,.18);
    background:linear-gradient(180deg,color-mix(in oklab, var(--accent) 8%, #fff), color-mix(in oklab, var(--accent) 5%, #fff));
  }
  .thumb .num{
    display:inline-block; margin:12px auto 0;
    padding:8px 18px; min-width:44px; text-align:center;
    font-weight:800; font-size:16px; letter-spacing:.25px; color:#fff;
    border-radius:12px;
    background:linear-gradient(180deg, color-mix(in oklab, var(--accent) 90%, #000 0%), color-mix(in oklab, var(--accent) 70%, #000 0%));
    box-shadow:0 6px 16px color-mix(in oklab, var(--accent) 35%, transparent);
  }

  /* ===== Document column ===== */
  .doc{ position:relative; overflow:auto; background: var(--doc-grad); }
  .viewer{
    width:min(1080px, 100% - 80px);
    margin:28px auto; display:flex; flex-direction:column; gap:24px; align-items:center;
  }
  .page{
    position:relative; border-radius:16px; overflow:hidden; background:#fff; color:#111;
    border:1px solid color-mix(in oklab, var(--divider), #000 2%);
    box-shadow: var(--shadow);
  }
  .page canvas{ display:block; width:100%; height:auto }
  .textLayer{ position:absolute; inset:0; pointer-events:none }
  .watermark{
    position:absolute; inset:0; display:grid; place-items:center; pointer-events:none;
    opacity:.10; font-weight:800; font-size:48px; transform:rotate(-18deg); color:var(--watermark);
  }

  /* Page HUD */
  .hud{
    position:absolute; left:50%; bottom:18px; transform:translateX(-50%);
    background:rgba(0,0,0,.75); color:#fff; padding:6px 14px; border-radius:12px;
    font-weight:800; font-size:.9rem; letter-spacing:.25px;
    box-shadow:0 10px 26px rgba(0,0,0,.28); backdrop-filter: blur(3px);
  }
  body[data-theme="light"] .hud{ background:rgba(15,23,42,.8); color:#fff; }

  /* Outline items (optional) */
  .panel-outline ul{ list-style:none; margin:0; padding:0 2px }
  .panel-outline li{
    padding:8px 10px; border-radius:10px; cursor:pointer;
    color:var(--text); border:1px solid transparent;
  }
  .panel-outline li:hover{ background:color-mix(in oklab, var(--accent) 10%, transparent) }
  .panel-outline li.active{
    background:color-mix(in oklab, var(--accent) 18%, transparent);
    border-color:color-mix(in oklab, var(--accent) 50%, transparent);
    box-shadow: inset 0 0 0 1px color-mix(in oklab, var(--accent) 40%, transparent);
  }

  @media (max-width:1200px){
    :root{ --sidebar-w: 250px; --divider-w: 8px; }
    .viewer{ width:min(980px, 100% - 64px) }
  }
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <button id="toggleSidebar" class="iconbtn" title="Toggle sidebar" aria-pressed="true">≡</button>

  <div class="spacer"></div>

  <div class="seg">
    <button id="zoomIn"  class="iconbtn" title="Zoom in">＋</button>
    <button id="zoomOut" class="iconbtn" title="Zoom out">－</button>
  </div>

  <select id="zoomSelect" class="zoom" title="Zoom">
    <option value="fitW">Fit to Width</option>
    <option value="fitH">Fit to Page</option>
    <option value="auto">Auto Zoom</option>
    <option value="actual">Actual Size</option>
    <option disabled>────────────</option>
    <option value="50">50%</option>
    <option value="75">75%</option>
    <option value="100" selected>100%</option>
    <option value="125">125%</option>
    <option value="150">150%</option>
    <option value="170">170%</option>
    <option value="200">200%</option>
    <option value="300">300%</option>
    <option value="400">400%</option>
  </select>

  <div class="spacer"></div>

  <button id="fullscreenBtn" class="iconbtn" title="Fullscreen">⛶</button>
  <div class="settings">
    <button id="settingsBtn" class="iconbtn" title="Settings">⚙</button>
    <div id="settingsMenu" class="dropdown">
      <button id="toggleTheme" class="item">Light mode</button>
    </div>
  </div>
</div>

<!-- App shell -->
<div class="shell">
  <!-- Sidebar (sticky tabs + scroll panels) -->
  <aside id="sidebar" class="sidebar">
    <div class="tabs sticky">
      <div class="seg2">
        <button id="tabThumbs"  class="tab active" title="Thumbnails" aria-pressed="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <rect x="3" y="3" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.8"/>
            <rect x="13" y="3" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.8"/>
            <rect x="3" y="13" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.8"/>
            <rect x="13" y="13" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.8"/>
          </svg>
        </button>
        <button id="tabOutline" class="tab" title="Outline" aria-pressed="false">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M6 6h14M6 12h14M6 18h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <circle cx="3" cy="6" r="1.2" fill="currentColor"/>
            <circle cx="3" cy="12" r="1.2" fill="currentColor"/>
            <circle cx="3" cy="18" r="1.2" fill="currentColor"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="panel panel-thumbs" id="thumbsPanel"></div>
    <div class="panel panel-outline" id="outlinePanel" style="display:none"></div>
  </aside>

  <!-- Divider -->
  <div class="vbar"></div>

  <!-- Document column -->
  <div id="doc" class="doc">
    <div class="viewer"></div>
    <div id="pageHUD" class="hud">1/1</div>
  </div>
</div>

<!-- Your custom renderer -->
<script type="module" src="assets/viewer.js"></script>
<script>
  // Boot data for viewer.js
  window.__PDF_VIEWER_BOOTSTRAP__ = {
    streamUrl: "<?= APP_BASE_URL ?>/api/pdf.php?tok=<?= urlencode($token) ?>",
    allowSearch: <?= (int)$link['allow_search'] ?>,
  };

  // Settings dropdown + theme persistence
  (()=>{
    const btn   = document.getElementById('settingsBtn');
    const menu  = document.getElementById('settingsMenu');
    const theme = document.getElementById('toggleTheme');

    // Load saved theme
    try {
      const saved = localStorage.getItem('pdfv_theme');
      if (saved === 'light') {
        document.body.setAttribute('data-theme','light');
        theme.textContent = 'Dark mode';
      }
    } catch {}

    btn.addEventListener('click', ()=> menu.classList.toggle('show'));
    document.addEventListener('click', (e)=>{
      if(!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.remove('show');
    });

    theme.addEventListener('click', ()=>{
      const isLight = document.body.getAttribute('data-theme')==='light';
      if (isLight) {
        document.body.removeAttribute('data-theme');
        theme.textContent = 'Light mode';
        try { localStorage.setItem('pdfv_theme','dark'); } catch {}
      } else {
        document.body.setAttribute('data-theme','light');
        theme.textContent = 'Dark mode';
        try { localStorage.setItem('pdfv_theme','light'); } catch {}
      }
      menu.classList.remove('show');
    });
  })();
</script>
</body>
</html>

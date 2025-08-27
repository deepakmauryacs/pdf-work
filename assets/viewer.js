// /app/assets/viewer.js
import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.54/build/pdf.mjs';
pdfjsLib.GlobalWorkerOptions.workerSrc =
  'https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.54/build/pdf.worker.mjs';

const cfg       = window.__PDF_VIEWER_BOOTSTRAP__ || {};
const viewer    = document.querySelector('.viewer');
const docEl     = document.getElementById('doc');
const sidebar   = document.getElementById('sidebar');
const thumbsEl  = document.getElementById('thumbsPanel');
const outlineEl = document.getElementById('outlinePanel');
const pageHUD   = document.getElementById('pageHUD');

const $ = id => document.getElementById(id);
const els = {
  toggleSidebar: $('toggleSidebar'),
  tabThumbs: $('tabThumbs'),
  tabOutline: $('tabOutline'),
  zoomIn: $('zoomIn'),
  zoomOut: $('zoomOut'),
  zoomSelect: $('zoomSelect'),
  fullscreenBtn: $('fullscreenBtn'),
};

// ----- state -----
let pdfDoc = null;
let pages = [];
let currentPage = 1;
let rotation = 0;
let zoomMode = 'percent';
let zoomPercent = 1;
let io = null;
let pendingScroll = 1;         // if scroll requested before pages exist

// ----- utils -----
const nextFrame = () => new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));
function setHUD(){ pageHUD.textContent = `${currentPage}/${pdfDoc?pdfDoc.numPages:1}`; }
function ensureVisible(el,box){ if(!el||!box) return; const t=box.scrollTop,b=t+box.clientHeight,et=el.offsetTop,eb=et+el.offsetHeight; if(et<t) box.scrollTop=et-16; else if(eb>b) box.scrollTop=eb-box.clientHeight+16; }

function setCurrentPage(n){
  if(!pdfDoc) return; currentPage=n; setHUD();
  [...thumbsEl.querySelectorAll('.thumb')].forEach(x=>x.classList.remove('active'));
  const th=thumbsEl.querySelector(`[data-page="${n}"]`); if(th){ th.classList.add('active'); ensureVisible(th,thumbsEl); }
  [...outlineEl.querySelectorAll('li')].forEach(x=>x.classList.remove('active'));
  const ol=outlineEl.querySelector(`[data-page="${n}"]`); if(ol){ ol.classList.add('active'); ensureVisible(ol,outlineEl); }
}

/* Stable width from document column; never render when width is transiently small */
function computeScale(baseViewport){
  let w = docEl.clientWidth - 80;              // 40px padding both sides in .viewer
  if (w < 50) w = baseViewport.width;          // guard
  if (zoomMode === 'fitW') return w / baseViewport.width;
  if (zoomMode === 'fitH') {
    const vh = Math.max(300, docEl.clientHeight - 60);
    return vh / baseViewport.height;
  }
  if (zoomMode === 'actual') return 1;
  if (zoomMode === 'auto') return Math.min(1.25, w / baseViewport.width);
  return zoomPercent;
}

function makePageView(num){
  const root=document.createElement('div'); root.className='page'; root.dataset.pageNum=String(num);
  const canvas=document.createElement('canvas');
  const tl=document.createElement('div'); tl.className='textLayer';
  const wm=document.createElement('div'); wm.className='watermark'; wm.textContent=`Secure View — ${location.host||'localhost'}`;
  root.append(canvas,tl,wm); viewer.appendChild(root);
  return { num, root, canvas, ctx: canvas.getContext('2d',{alpha:false}), tl, rkey:null, task:null, requested:false };
}

async function renderPage(pv){
  if (docEl.clientWidth < 50) { setTimeout(()=>renderPage(pv), 50); return; } // wait layout
  const page = await pdfDoc.getPage(pv.num);
  const base = page.getViewport({ scale:1, rotation });
  const scale = computeScale(base);
  const viewport = page.getViewport({ scale, rotation });
  const dpr = window.devicePixelRatio || 1;

  const key = `${Math.round(viewport.width)}x${Math.round(viewport.height)}@${dpr}|rot${rotation}`;
  if (pv.rkey === key && !pv.requested) return;

  try{ pv.task?.cancel(); }catch{} pv.requested=false;

  pv.canvas.width  = Math.max(1, Math.floor(viewport.width  * dpr));
  pv.canvas.height = Math.max(1, Math.floor(viewport.height * dpr));
  pv.canvas.style.width  = Math.floor(viewport.width)  + 'px';
  pv.canvas.style.height = Math.floor(viewport.height) + 'px';

  pv.tl.innerHTML=''; pv.tl.style.width=pv.canvas.style.width; pv.tl.style.height=pv.canvas.style.height;

  pv.task = page.render({ canvasContext: pv.ctx, viewport, transform:[dpr,0,0,dpr,0,0] });
  try{ await pv.task.promise; }catch{ return; } finally{ pv.task=null; }

  if (cfg.allowSearch){
    try{
      const textContent = await page.getTextContent();
      await pdfjsLib.renderTextLayer({ textContentSource:textContent, container: pv.tl, viewport }).promise;
    }catch{}
  }
  pv.rkey = key;
}

/* Force re-render helper (used after sidebar toggle) */
function forceFullRerender(visibleOnly=false){
  const buffer=visibleOnly?1200:Infinity;
  const top=docEl.scrollTop-buffer, bottom=docEl.scrollTop+docEl.clientHeight+buffer;
  for(const pv of pages){
    const r=pv.root.getBoundingClientRect();
    const y1=r.top + docEl.scrollTop - docEl.getBoundingClientRect().top;
    const y2=y1+r.height;
    if(!visibleOnly || (y2>=top && y1<=bottom)){
      pv.rkey=null; pv.requested=true;
      try{ pv.task?.cancel(); }catch{}
      renderPage(pv);
    }
  }
}

function observePages(){
  if (io) io.disconnect();
  io = new IntersectionObserver(entries=>{
    for(const e of entries){
      if(e.isIntersecting){
        const pv = pages[+e.target.dataset.pageNum - 1];
        if(!pv.task) renderPage(pv);
      }
    }
    const top = docEl.scrollTop + 100;
    let best=currentPage, bestDist=Infinity;
    for(const pv of pages){
      const r=pv.root.getBoundingClientRect();
      const off=r.top + docEl.scrollTop - docEl.getBoundingClientRect().top;
      const d=Math.abs(off-top); if(d<bestDist){ bestDist=d; best=pv.num; }
    }
    setCurrentPage(best);
  },{ root:docEl, rootMargin:'1200px 0px 1400px 0px', threshold:0.01 });
  pages.forEach(pv=>io.observe(pv.root));
}

function scrollToPage(n){
  if (!pages.length){ pendingScroll = n; return; } // wait until pages exist
  const pv=pages[n-1]; if(!pv) return;
  pv.root.scrollIntoView({ behavior:'smooth', block:'start' });
  setCurrentPage(n);
}

// ----- thumbnails -----
async function buildThumbnails(){
  thumbsEl.innerHTML=''; const frag=document.createDocumentFragment();
  for(let i=1;i<=pdfDoc.numPages;i++){
    const page=await pdfDoc.getPage(i);
    const base=page.getViewport({scale:1});
    const scale=Math.min(190/base.width, 270/base.height);
    const vp=page.getViewport({scale});
    const c=document.createElement('canvas'); const dpr=window.devicePixelRatio||1;
    c.width=Math.floor(vp.width*dpr); c.height=Math.floor(vp.height*dpr);
    c.style.width=Math.floor(vp.width)+'px'; c.style.height=Math.floor(vp.height)+'px';
    await page.render({canvasContext:c.getContext('2d'), viewport:vp, transform:[dpr,0,0,dpr,0,0]}).promise;
    const wrap=document.createElement('div'); wrap.className='thumb'; wrap.dataset.page=String(i); wrap.append(c);
    const num=document.createElement('div'); num.className='num'; num.textContent=i; wrap.append(num);
    wrap.addEventListener('click',()=>scrollToPage(i)); frag.append(wrap);
  }
  thumbsEl.append(frag); const first=thumbsEl.querySelector('.thumb'); if(first) first.classList.add('active');
}

// ----- outline -----
async function buildOutline(){
  outlineEl.innerHTML=''; const outline=await pdfDoc.getOutline();
  if(!outline||!outline.length){ outlineEl.textContent='No outline available'; return; }
  const ul=document.createElement('ul'); ul.className='outline';
  async function add(items,parent){
    for(const it of items){
      let pageNum=null;
      try{
        if(Array.isArray(it.dest)) pageNum=(await pdfDoc.getPageIndex(it.dest[0]))+1;
        else if(it.dest){ const dest=await pdfDoc.getDestination(it.dest); if(dest&&dest[0]) pageNum=(await pdfDoc.getPageIndex(dest[0]))+1; }
      }catch{}
      const li=document.createElement('li'); li.textContent=it.title||'(untitled)';
      if(pageNum){ li.dataset.page=String(pageNum); li.addEventListener('click',()=>scrollToPage(pageNum)); }
      parent.append(li);
      if(it.items&&it.items.length){ const sub=document.createElement('ul'); sub.className='outline'; li.append(sub); await add(it.items,sub); }
    }
  }
  await add(outline,ul); outlineEl.append(ul);
}

// ----- zoom -----
function updateZoomSelect(){
  if(['fitW','fitH','auto','actual'].includes(zoomMode)){ els.zoomSelect.value=zoomMode; return; }
  const pct=Math.round(zoomPercent*100);
  const nums=[...els.zoomSelect.options].map(o=>o.value).filter(v=>/^\d+$/.test(v)).map(Number);
  let best=nums[0],dist=Infinity; for(const n of nums){ const d=Math.abs(n-pct); if(d<dist){ dist=d; best=n; } }
  els.zoomSelect.value=String(best);
}

let rerenderTimer=null;
function scheduleRerender(){
  clearTimeout(rerenderTimer);
  rerenderTimer=setTimeout(()=>{
    if (docEl.clientWidth < 50){ setTimeout(scheduleRerender, 50); return; }
    forceFullRerender(true);
  }, 80);
}

// ----- sidebar toggle (fixed flow) -----
(function initSidebarToggle(){
  const shell=document.querySelector('.shell');
  async function apply(collapsed){
    shell.classList.toggle('collapsed', collapsed);
    els.toggleSidebar.setAttribute('aria-pressed', (!collapsed).toString());
    try{ localStorage.setItem('pdfv_sidebar', collapsed?'1':'0'); }catch{}
    await nextFrame(); await nextFrame();   // wait layout settle
    forceFullRerender(true);
    scrollToPage(currentPage);
    setTimeout(()=>{ forceFullRerender(true); scrollToPage(currentPage); }, 120); // late pass
  }
  let collapsed=false; try{ collapsed = localStorage.getItem('pdfv_sidebar')==='1'; }catch{}
  // apply initial (does nothing harmful before pages exist)
  apply(collapsed);
  els.toggleSidebar.addEventListener('click', ()=> apply(!shell.classList.contains('collapsed')));
})();

// ----- wiring -----
els.zoomIn.addEventListener('click',()=>{ zoomMode='percent'; zoomPercent=Math.min(4, zoomPercent*1.1); updateZoomSelect(); scheduleRerender(); });
els.zoomOut.addEventListener('click',()=>{ zoomMode='percent'; zoomPercent=Math.max(0.2, zoomPercent/1.1); updateZoomSelect(); scheduleRerender(); });
els.zoomSelect.addEventListener('change',()=>{
  const v=els.zoomSelect.value;
  if(/^\d+$/.test(v)){ zoomMode='percent'; zoomPercent=parseInt(v,10)/100; }
  else{ zoomMode=v; if(v==='actual') zoomPercent=1; }
  scheduleRerender();
});
els.fullscreenBtn.addEventListener('click',()=>{ const r=document.documentElement; if(!document.fullscreenElement) r.requestFullscreen?.(); else document.exitFullscreen?.(); });
els.tabThumbs.addEventListener('click',()=>{ els.tabThumbs.classList.add('active'); els.tabThumbs.setAttribute('aria-pressed','true'); els.tabOutline.classList.remove('active'); els.tabOutline.setAttribute('aria-pressed','false'); thumbsEl.style.display=''; outlineEl.style.display='none'; });
els.tabOutline.addEventListener('click',()=>{ els.tabOutline.classList.add('active'); els.tabOutline.setAttribute('aria-pressed','true'); els.tabThumbs.classList.remove('active'); els.tabThumbs.setAttribute('aria-pressed','false'); outlineEl.style.display=''; thumbsEl.style.display='none'; });
window.addEventListener('keydown',(e)=>{
  if(e.key==='ArrowRight'||e.key==='PageDown'||(e.code==='Space'&&!e.shiftKey)){ e.preventDefault(); scrollToPage(Math.min(currentPage+1, pdfDoc.numPages)); }
  if(e.key==='ArrowLeft'||e.key==='PageUp'||(e.code==='Space'&&e.shiftKey)){ e.preventDefault(); scrollToPage(Math.max(currentPage-1, 1)); }
});
docEl.addEventListener('scroll',()=>setHUD());
window.addEventListener('resize', scheduleRerender);

// detect true size change of the doc column
(() => {
  let w=docEl.clientWidth, h=docEl.clientHeight;
  const ro=new ResizeObserver(()=>{
    const nw=docEl.clientWidth, nh=docEl.clientHeight;
    if(Math.abs(nw-w)>2||Math.abs(nh-h)>2){ w=nw; h=nh; scheduleRerender(); }
  });
  ro.observe(docEl);
})();

// ----- boot -----
(async function boot(){
  const resp=await fetch(cfg.streamUrl,{credentials:'include'});
  if(!resp.ok){ console.error('PDF stream failed', resp.status); return; }
  const buf=await resp.arrayBuffer();
  pdfDoc=await pdfjsLib.getDocument({data:buf}).promise;

  viewer.innerHTML=''; pages=[];
  for(let i=1;i<=pdfDoc.numPages;i++) pages.push(makePageView(i));

  setHUD(); observePages();
  renderPage(pages[0]).catch(()=>{}); if(pages[1]) renderPage(pages[1]).catch(()=>{});

  await buildThumbnails(); await buildOutline();

  // honor any pending scroll (e.g., if sidebar init ran before pages existed)
  if (pendingScroll) scrollToPage(pendingScroll);
})();

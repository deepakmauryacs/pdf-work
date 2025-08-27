import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.54/build/pdf.mjs';
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.54/build/pdf.worker.mjs';

const cfg = window.__PDF_VIEWER_BOOTSTRAP__;
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
const textLayerDiv = document.getElementById('textLayer');
const dpr = window.devicePixelRatio || 1;

let pdfDoc = null, pageNum = 1, rotation = 0, zoom = 1;

const $ = id => document.getElementById(id);
const els = { prev:$('prev'), next:$('next'), pageNumber:$('pageNumber'), pageCount:$('pageCount'), zoomIn:$('zoomIn'), zoomOut:$('zoomOut'), fit:$('fit'), rotate:$('rotate') };

function setDisabled(){ els.prev.disabled = pageNum<=1; els.next.disabled = pdfDoc && pageNum>=pdfDoc.numPages; }

async function renderPage(n){
  const page = await pdfDoc.getPage(n);
  const containerWidth = document.querySelector('.viewer').clientWidth - 32;
  const base = page.getViewport({scale:1, rotation});
  let scale = zoom;
  if(els.fit.value==='fitW') scale = containerWidth / base.width;
  else if(els.fit.value==='fitH'){ const vh = window.innerHeight - 140; scale = vh / base.height; }
  else if(els.fit.value==='actual') scale = 1;
  else if(els.fit.value==='auto') scale = Math.min(1.2, containerWidth / base.width);

  const viewport = page.getViewport({scale, rotation});
  canvas.width = Math.floor(viewport.width * dpr);
  canvas.height = Math.floor(viewport.height * dpr);
  canvas.style.width = Math.floor(viewport.width) + 'px';
  canvas.style.height = Math.floor(viewport.height) + 'px';

  textLayerDiv.innerHTML = '';
  textLayerDiv.style.width = canvas.style.width;
  textLayerDiv.style.height = canvas.style.height;

  await page.render({canvasContext: ctx, viewport, transform:[dpr,0,0,dpr,0,0]}).promise;

  if(cfg.allowSearch){
    try{
      const textContent = await page.getTextContent();
      const textLayer = new pdfjsLib.TextLayer({ textContentSource: textContent, container: textLayerDiv, viewport });
      textLayer.render();
    }catch(e){}
  }

  els.pageNumber.value = n; setDisabled();
}

async function load(){
  const resp = await fetch(cfg.streamUrl, { credentials:'include' });
  const buf = await resp.arrayBuffer();
  const task = pdfjsLib.getDocument({ data: buf, disableAutoFetch: false, disableStream: false });
  pdfDoc = await task.promise;
  els.pageCount.textContent = pdfDoc.numPages;
  pageNum = 1; await renderPage(pageNum);
}

els.prev.onclick = async ()=>{ if(pageNum>1){ pageNum--; await renderPage(pageNum);} };
els.next.onclick = async ()=>{ if(pageNum<pdfDoc.numPages){ pageNum++; await renderPage(pageNum);} };
els.zoomIn.onclick = async ()=>{ zoom *= 1.1; await renderPage(pageNum); };
els.zoomOut.onclick = async ()=>{ zoom = Math.max(0.2, zoom/1.1); await renderPage(pageNum); };
els.rotate.onclick = async ()=>{ rotation = (rotation+90)%360; await renderPage(pageNum); };
els.pageNumber.onchange = async (e)=>{ const v = Math.min(Math.max(1,+e.target.value), pdfDoc.numPages); pageNum=v; await renderPage(pageNum); };
els.fit.onchange = async ()=>{ await renderPage(pageNum); };
window.addEventListener('resize', ()=>renderPage(pageNum));

load();

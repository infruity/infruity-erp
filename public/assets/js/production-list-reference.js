/* Presentation only: existing production endpoints remain authoritative. */
(function(root,factory){const api=factory();if(typeof module==='object'&&module.exports)module.exports=api;else root.ProductionListUi=api;})(typeof globalThis!=='undefined'?globalThis:this,function(){
 'use strict';
 function recordedTime(value){
  const m=/^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})$/.exec(String(value||''));
  if(!m)return '—';
  const [day,month,year,hour,minute]=m.slice(1).map(Number);
  const d=new Date(Date.UTC(year,month-1,day,hour,minute));
  if(d.getUTCFullYear()!==year||d.getUTCMonth()!==month-1||d.getUTCDate()!==day||d.getUTCHours()!==hour||d.getUTCMinutes()!==minute)return '—';
  return `${day} ${['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][month-1]} ${year} · ${m[4]}:${m[5]}`;
 }
 function statusLabel(kind){return {'is-temp':'Draf','is-draft':'Siap Produksi','is-posting':'Selesai','is-complete':'Selesai'}[kind]||null;}
 function quantity(value){
  if(value==null||value===''||!Number.isFinite(Number(value)))return null;
  return Number(value).toLocaleString('id-ID',{maximumFractionDigits:20});
 }
 const detailTags=new Set(['div','section','span','i','h1','h2','p','dl','dt','dd','a','strong','small','br','em','ul','ol','li','time']);
 const detailClasses=new Set(['btn','btn-light','btn-light-info','btn-light-primary','btn-success','is-done','is-note','is-pos','is-ready','is-temp','me-1','ph','ph-arrow-left','ph-check','ph-orange','ph-pencil-simple','ph-printer','production-detail','production-detail__actions','production-detail__avatar','production-detail__badge','production-detail__body','production-detail__card','production-detail__empty','production-detail__head','production-detail__ingredient','production-detail__ingredient-cost','production-detail__ingredient-name','production-detail__meta','production-detail__note','production-detail__section-title','production-detail__status','production-detail__title','production-detail__total','pt-0']);
 const detailIds=new Set(['production-detail-title','production-ingredients-title','production-extra-title']);
 const detailAttributes=new Set(['class','id','aria-label','aria-hidden','aria-labelledby','href']);
 const detailVoidTags=new Set(['br']);
 function escapeDetailText(value){return String(value).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
 function escapeDetailAttribute(value){return String(value).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
 function serializeDetailNode(node){
  if(node.nodeType===3)return escapeDetailText(node.nodeValue||'');
  if(node.nodeType!==1)return '';
  const tag=node.tagName.toLowerCase();
  if(!detailTags.has(tag))return '';
  const attributes=Array.from(node.attributes).filter(attribute=>detailAttributes.has(attribute.name.toLowerCase())||(attribute.name.toLowerCase()==='data-production-detail-back'&&attribute.value==='true')).map(attribute=>` ${attribute.name.toLowerCase()}="${escapeDetailAttribute(attribute.value)}"`).join('');
  if(detailVoidTags.has(tag))return `<${tag}${attributes}>`;
  return `<${tag}${attributes}>${Array.from(node.childNodes).map(serializeDetailNode).join('')}</${tag}>`;
 }
 function sanitizeDetailElement(element,baseUrl){
  const tag=element.tagName.toLowerCase();
  if(!detailTags.has(tag)){element.remove();return;}
  Array.from(element.attributes).forEach(attribute=>{
   const name=attribute.name.toLowerCase(),value=attribute.value;
   if(name==='href'){
    try{
     const link=new URL(value,baseUrl);
     if(link.origin===new URL(baseUrl).origin&&(link.protocol==='http:'||link.protocol==='https:'))element.setAttribute('href',link.href);
     else element.removeAttribute(attribute.name);
    }catch(_error){element.removeAttribute(attribute.name);}
   }else if(name==='class'){
    const classes=[...new Set(value.split(/\s+/).filter(token=>detailClasses.has(token)))];
    if(classes.length)element.setAttribute('class',classes.join(' '));else element.removeAttribute('class');
   }else if(name==='id'){
    if(!detailIds.has(value))element.removeAttribute(attribute.name);
   }else if(name==='aria-hidden'){
    if(value!=='true'&&value!=='false')element.removeAttribute(attribute.name);
   }else if(name==='aria-label'){
    if(value!=='Ringkasan biaya')element.removeAttribute(attribute.name);
   }else if(!detailAttributes.has(name))element.removeAttribute(attribute.name);
  });
  Array.from(element.children).forEach(child=>sanitizeDetailElement(child,baseUrl));
 }
 function namespaceDetailIds(detail,document){
  const groups=new Map(),idMap=new Map();
  [detail,...detail.querySelectorAll('[id]')].forEach(element=>{
   const original=element.getAttribute('id');
   if(!detailIds.has(original)){element.removeAttribute('id');return;}
   if(!groups.has(original))groups.set(original,[]);
   groups.get(original).push(element);
  });
  groups.forEach((elements,original)=>{
   if(elements.length!==1){elements.forEach(element=>element.removeAttribute('id'));return;}
   const baseId=`production-detail-modal-fragment-${original}`;
   let safeId=baseId,suffix=2;
   while(document.getElementById(safeId))safeId=`${baseId}-${suffix++}`;
   idMap.set(original,safeId);
   elements[0].setAttribute('id',safeId);
  });
  [detail,...detail.querySelectorAll('[aria-labelledby]')].forEach(element=>{
   const references=(element.getAttribute('aria-labelledby')||'').split(/\s+/).map(id=>idMap.get(id)).filter(Boolean);
   if(references.length)element.setAttribute('aria-labelledby',references.join(' '));
   else element.removeAttribute('aria-labelledby');
  });
 }
 function enhanceRow(row,data){
  const subtitle=row.querySelector('.production-sub');
  if(subtitle){subtitle.textContent=subtitle.textContent.replace(/\s*·\s*\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}(?=\s*·|$)/,'');}
  const copy=row.querySelector('.production-copy');
  if(copy&&!copy.querySelector('.production-reference-time')){
   const stamp=row.querySelector('td:nth-child(3) span');
   const time=document.createElement('span');time.className='production-reference-time';time.setAttribute('aria-label','Waktu dibuat');time.textContent=recordedTime(stamp?.textContent.trim());copy.append(time);
  }
  const status=row.querySelector('.production-status');
  if(status){const label=[...status.classList].map(statusLabel).find(Boolean);if(label)status.textContent=label;}
  const qty=row.querySelector('.production-qty'),display=quantity(data.quantity);
  if(qty&&display!==null){qty.textContent=display;qty.setAttribute('aria-label','Hasil produksi: '+display);}
 }
 function createDetailModal(indexUrl,environment={}){
  const doc=environment.document||(typeof document!=='undefined'?document:null);
  const win=environment.window||(typeof window!=='undefined'?window:null);
  const fetcher=environment.fetch||(typeof fetch!=='undefined'?fetch:null);
  const Parser=environment.DOMParser||(typeof DOMParser!=='undefined'?DOMParser:null);
  const raf=environment.requestAnimationFrame||(win?.requestAnimationFrame?win.requestAnimationFrame.bind(win):callback=>setTimeout(callback,0));
  const setTimer=environment.setTimeout||setTimeout,clearTimer=environment.clearTimeout||clearTimeout;
  const listUrl=new URL(indexUrl,win.location.href);
  const modalStateKey='infruityProductionDetailModal',modalUrlKey='infruityProductionDetailUrl',modalEntryKey='infruityProductionDetailEntry';
  let generation=0,previousFocus=null,previousContentInert=null,clickHandler=null,popstateHandler=null,closeTimer=null,modalEntrySequence=0,fallbackListEntry=null;
  const modalHistoryEntries=new Map();
  const isPlainClick=(event,link)=>event.button===0&&!event.metaKey&&!event.ctrlKey&&!event.shiftKey&&!event.altKey&&(!link.target||link.target==='_self')&&!link.hasAttribute('download');
  function activeModalHistoryUrl(state=win.history.state){
   if(state?.[modalStateKey]!==true||typeof state[modalUrlKey]!=='string')return null;
   try{
    const target=new URL(state[modalUrlKey],win.location.href);
    return target.origin===win.location.origin&&target.href===win.location.href?target.href:null;
   }catch(_error){return null;}
  }
  function cancelCloseTimer(){if(closeTimer!==null){clearTimer(closeTimer);closeTimer=null;}}
  function finishClose(controller){
   cancelCloseTimer();
   generation+=1;
   controller.productionDetailOpen=false;
   controller.productionDetailLoading=false;
   controller.productionDetailHtml='';
   const content=doc.getElementById('kt_content');
   content?.classList.remove('production-detail-open');
   if(content&&previousContentInert!==null)content.inert=previousContentInert;
   previousContentInert=null;
   const focusTarget=previousFocus;previousFocus=null;
   if(focusTarget?.isConnected)focusTarget.focus();
  }
  return {
   productionDetailOpen:false,
   productionDetailLoading:false,
   productionDetailHtml:'',
   productionDetailTitle:'Detail Produksi',
   init(){
    win.openProductionDetail=url=>this.openProductionDetail(url);
    clickHandler=event=>{
     const closeLink=event.target?.closest?.('[data-production-detail-back]');
     if(closeLink){event.preventDefault();this.closeProductionDetail();return;}
     const link=event.target?.closest?.('a.production-copy');
     if(!link||!isPlainClick(event,link))return;
     event.preventDefault();this.openProductionDetail(link.href);
    };
    popstateHandler=event=>this.handleProductionDetailPopState(event);
    doc.addEventListener('click',clickHandler);
    win.addEventListener('popstate',popstateHandler);
   },
   destroy(){
    if(clickHandler)doc.removeEventListener('click',clickHandler);
    if(popstateHandler)win.removeEventListener('popstate',popstateHandler);
    if(win.openProductionDetail)delete win.openProductionDetail;
    finishClose(this);
   },
   async openProductionDetail(url,options={}){
    const requestId=++generation;
    let targetUrl,historyPushed=false;
    try{
     targetUrl=new URL(url,win.location.href);
     if(targetUrl.origin!==win.location.origin){win.location.assign(targetUrl.href);return;}
     if(!this.productionDetailOpen){
      previousFocus=doc.activeElement;
      previousContentInert=doc.getElementById('kt_content')?.inert??null;
     }
     if(options.pushHistory!==false){
      const previousEntry={url:win.location.href,state:win.history.state};
      if(!activeModalHistoryUrl(previousEntry.state))fallbackListEntry=previousEntry;
      let entryId=modalEntrySequence+1;
      while(entryId===previousEntry.state?.[modalEntryKey]||modalHistoryEntries.has(entryId))entryId+=1;
      modalEntrySequence=entryId;
      modalHistoryEntries.set(entryId,previousEntry);
      const state=previousEntry.state&&typeof previousEntry.state==='object'?{...previousEntry.state}:{};
      win.history.pushState({...state,[modalStateKey]:true,[modalUrlKey]:targetUrl.href,[modalEntryKey]:entryId},'',targetUrl.href);
      historyPushed=true;
     }
     this.productionDetailTitle='Detail Produksi';
     this.productionDetailHtml='';
     this.productionDetailLoading=true;
     this.productionDetailOpen=true;
     const content=doc.getElementById('kt_content');
     content?.classList.add('production-detail-open');
     if(content)content.inert=true;
     const focusCloseButton=()=>raf(()=>doc.querySelector('.production-detail-dialog [aria-label="Tutup detail produksi"]')?.focus());
     if(typeof this.$nextTick==='function')this.$nextTick(focusCloseButton);else focusCloseButton();

     const response=await fetcher(targetUrl.href,{credentials:'same-origin',headers:{Accept:'text/html','X-Requested-With':'XMLHttpRequest'}});
     if(requestId!==generation)return;
     if(!response.ok)throw new Error(`Detail request failed: ${response.status}`);
     const html=await response.text();
     if(requestId!==generation)return;
     const parsed=new Parser().parseFromString(html,'text/html');
     const detail=parsed.querySelector('.production-detail');
     if(!detail||detail.tagName.toLowerCase()!=='div')throw new Error('Rendered production detail is missing');
     const title=detail.querySelector('#production-detail-title')?.textContent.trim()||'Detail Produksi';
     sanitizeDetailElement(detail,targetUrl.href);
     namespaceDetailIds(detail,doc);
     detail.querySelectorAll('.production-detail__actions a[href]').forEach(link=>{
      if(new URL(link.getAttribute('href'),targetUrl.href).pathname===listUrl.pathname)link.dataset.productionDetailBack='true';
     });
     this.productionDetailTitle=title;
     this.productionDetailHtml=serializeDetailNode(detail);
     this.productionDetailLoading=false;
    }catch(error){
     if(requestId!==generation)return;
     const fallbackUrl=targetUrl?.href||url;
     const replaceHistoryEntry=historyPushed||activeModalHistoryUrl();
     finishClose(this);
     if(replaceHistoryEntry)win.location.replace(fallbackUrl);
     else win.location.assign(fallbackUrl);
    }
   },
   trapProductionDetailFocus(event){
    const dialog=doc.getElementById('production-detail-modal');
    if(!dialog)return;
    const focusable=Array.from(dialog.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')).filter(node=>node.getClientRects().length>0);
    if(!focusable.length){event.preventDefault();dialog.focus();return;}
    const first=focusable[0],last=focusable[focusable.length-1],outside=!dialog.contains(doc.activeElement);
    if(event.shiftKey&&(doc.activeElement===first||outside)){event.preventDefault();last.focus();}
    else if(!event.shiftKey&&(doc.activeElement===last||outside)){event.preventDefault();first.focus();}
   },
   closeProductionDetail(){
    const closingUrl=activeModalHistoryUrl();
    if(!closingUrl){finishClose(this);return;}
    if(closeTimer!==null)return;
    const closingState=win.history.state,entryId=closingState?.[modalEntryKey];
    generation+=1;
    win.history.back();
    closeTimer=setTimer(()=>{
     closeTimer=null;
     if(!this.productionDetailOpen)return;
     const currentState=win.history.state,currentModalUrl=activeModalHistoryUrl(currentState);
     if(currentModalUrl&&currentState[modalEntryKey]!==entryId){
      this.openProductionDetail(currentState[modalUrlKey],{pushHistory:false});
      return;
     }
     if(!currentModalUrl&&win.location.href!==closingUrl){finishClose(this);return;}
     const previous=modalHistoryEntries.get(entryId)||fallbackListEntry||{url:listUrl.href,state:{}};
     win.history.replaceState(previous.state,'',previous.url);
     if(activeModalHistoryUrl(previous.state))this.openProductionDetail(previous.state[modalUrlKey],{pushHistory:false});
     else finishClose(this);
    },500);
   },
   async handleProductionDetailPopState(event){
    cancelCloseTimer();
    if(activeModalHistoryUrl(event.state)){return this.openProductionDetail(event.state[modalUrlKey],{pushHistory:false});}
    finishClose(this);
   },
  };
 }
 return {recordedTime,statusLabel,quantity,enhanceRow,createDetailModal};
});

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
 return {recordedTime,statusLabel,quantity,enhanceRow};
});

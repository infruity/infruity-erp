/* A mobile list is rendered from the current DataTables page, while business
 * actions remain owned by the original table. No second API or data cache. */
(() => {
 'use strict';
 const text = element => {if(!element)return '';const select=element.matches?.('select')?element:element.querySelector?.('select');if(select)return select.selectedOptions[0]?.textContent.trim()||'';const copy=element.cloneNode(true);copy.querySelectorAll('br').forEach(e=>e.replaceWith(' '));copy.querySelectorAll('div,p,span,li').forEach(e=>e.append(' '));return copy.textContent.replace(/\s+/g,' ').trim();};
 const node = (tag, cls, value) => { const e=document.createElement(tag);if(cls)e.className=cls;if(value!==undefined)e.textContent=value;return e; };
 const states = new WeakMap();
 function apiFor(table) { return window.jQuery?.fn?.dataTable?.isDataTable(table) ? window.jQuery(table).DataTable() : null; }
 function actionCopy(original, label) {
  const copy=node(original.tagName==='A'?'a':'button','',label || text(original) || original.getAttribute('aria-label') || original.title || 'Buka');
  if(copy.tagName==='A')copy.href=original.getAttribute('href')||'#';else copy.type='button';
  copy.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();original.click();});
  return copy;
 }
 function render(host) {
  const id=host.dataset.erpCollection, table=document.getElementById(id);if(!table)return;
  let state=states.get(host);if(!state){state={open:new Set(),list:node('div','erp-record-list')};host.classList.add('erp-collection');host.append(state.list);states.set(host,state);}
  const api=apiFor(table);const rows=[...table.tBodies].flatMap(body=>[...body.rows]).filter(r=>!r.classList.contains('child')&&r.cells.length>1&&r.style.display!=='none');
  const headings=api?api.settings()[0].aoColumns.map(column=>{const label=document.createElement('span');label.innerHTML=column.sTitle||'';return text(label);}):[...table.tHead?.rows[0]?.cells||[]].map(text);state.list.replaceChildren();
  if(api){
   const controls=node('div','erp-record-controls'),sort=node('select',''),sortLabel=node('label','','Urutkan ');sort.setAttribute('aria-label','Urutkan data');
   const order=api.order()[0]||[0,'asc'];headings.forEach((heading,i)=>{if(!heading||(api.settings()[0].aoColumns[i]?.bSortable===false || api.settings()[0].aoColumns[i]?.bVisible===false))return;const option=node('option','',heading);option.value=i;option.selected=i===order[0];sort.append(option);});
   sort.onchange=()=>api.order([Number(sort.value),order[1]]).draw();sortLabel.append(sort);const direction=node('button','',order[1]==='asc'?'↑':'↓');direction.type='button';direction.setAttribute('aria-label',order[1]==='asc'?'Urutkan menurun':'Urutkan menaik');direction.onclick=()=>api.order([Number(sort.value),order[1]==='asc'?'desc':'asc']).draw();
   const length=node('select','');length.setAttribute('aria-label','Jumlah data per halaman');[10,25,50,100,-1].forEach(v=>{const o=node('option','',v===-1?'Semua':String(v));o.value=v;o.selected=api.page.len()===v;length.append(o)});length.onchange=()=>api.page.len(Number(length.value)).draw();controls.append(sortLabel,direction,length);state.list.append(controls);
  }

  if(!rows.length){const empty=node('div','erp-record-empty');empty.append(node('i','ph ph-tray'),node('p','',table.querySelector('.dataTables_empty')?text(table.querySelector('.dataTables_empty')):'Belum ada data untuk ditampilkan.'));state.list.append(empty);}
  rows.forEach((row,index)=>{
   const cells=[...row.cells], meaningful=cells.map((c,i)=>({c,i,t:text(c)})).filter(x=>x.t && !/^\s*(aksi|action)?\s*$/i.test(x.t) && !x.c.querySelector('[data-erp-row-actions]'));const identity=meaningful.find(x=>/nama|name|pelanggan|produk|product|remarks|cabang|keterangan/i.test(headings[x.i]||''))||meaningful.find(x=>!/^\d+$/.test(x.t))||meaningful[0];if(!identity)return;
   const record=node('article','erp-record'),head=node('button','erp-record__head');head.type='button';const recordKey=row.id||text(row).slice(0,180);const expanded=state.open.has(recordKey);head.setAttribute('aria-expanded',String(expanded));
   const avatar=node('span','erp-record__avatar');avatar.append(node('i','ph ph-receipt'));const identityWrap=node('span','erp-record__identity');const nameElement=identity.c.querySelector('a.text-gray-800,.fw-bold,.fw-bolder,h6,h5,strong');const title=node('span','erp-record__title',text(nameElement)||identity.t);identityWrap.append(title);
   const meta=meaningful.filter(x=>x!==identity && !x.c.querySelector('button,[onclick],.menu')).map(x=>x.t).slice(0,2).join(' · ');identityWrap.append(node('span','erp-record__meta',meta));head.append(avatar,identityWrap,node('i','ph ph-caret-down erp-record__chevron'));record.append(head);
   const details=node('div','erp-record__details');details.hidden=!expanded;details.id='erp-record-'+id+'-'+index;head.setAttribute('aria-controls',details.id);
   cells.forEach((cell,i)=>{const value=text(cell);if(!value || cell.querySelector('.menu,button[data-kt-menu-trigger],[data-erp-row-actions]'))return;const field=node('div','erp-record__field');const output=node('span','erp-record__value',value);const input=cell.querySelector('select,input:not([type=hidden])');if(input){const copy=input.cloneNode(true);copy.removeAttribute('id');copy.removeAttribute('name');[...copy.attributes].forEach(a=>{if(a.name.startsWith('on'))copy.removeAttribute(a.name)});copy.value=input.value;copy.setAttribute('aria-label',headings[i]||'Ubah nilai');copy.addEventListener('change',()=>{input.value=copy.value;if(input.type==='checkbox')input.checked=copy.checked;input.dispatchEvent(new Event('change',{bubbles:true}));});output.replaceChildren(copy);}field.append(node('span','',headings[i]||'Detail'),output);details.append(field);});
   const actions=node('div','erp-record__actions');const controls=[...row.querySelectorAll('a[href],button,[onclick]')].filter(c=>!c.matches('[data-kt-menu-trigger],[aria-haspopup="menu"]')&&!c.closest('thead')&&!c.querySelector('[onclick]'));
   const seen=new Set();controls.forEach(control=>{const signature=(control.getAttribute('href')||control.getAttribute('onclick')||'')+text(control);if(seen.has(signature))return;seen.add(signature);actions.append(actionCopy(control));});
   // Row click handlers (e.g. tier details) are preserved as an explicit action.
   if(table.hasAttribute('data-erp-row-details') || row.matches('[onclick]') || row.querySelector('.dt-control')){const original=table.hasAttribute('data-erp-row-details')||row.matches('[onclick]')?row:row.querySelector('.dt-control');actions.append(actionCopy(original,'Detail'));}
   if(actions.childElementCount)details.append(actions);record.append(details);head.addEventListener('click',()=>{const open=details.hidden;details.hidden=!open;head.setAttribute('aria-expanded',String(open));if(open)state.open.add(recordKey);else state.open.delete(recordKey);});state.list.append(record);
  });
  if(api){const info=api.page.info(),pager=node('div','erp-record-pager');const prev=node('button','','‹');prev.type='button';prev.setAttribute('aria-label','Halaman sebelumnya');prev.disabled=info.page===0;prev.onclick=()=>api.page('previous').draw('page');const next=node('button','','›');next.type='button';next.setAttribute('aria-label','Halaman berikutnya');next.disabled=info.page>=info.pages-1;next.onclick=()=>api.page('next').draw('page');pager.append(prev,node('span','',info.recordsDisplay ? `${info.start+1}–${info.end} dari ${info.recordsDisplay} data`:'0 data'),next);state.list.append(pager);}
  host.dataset.erpReady='true';
 }
 function start(){document.querySelectorAll('[data-erp-collection]').forEach(candidate=>{let host=candidate;if(candidate.tagName==='TABLE'){host=node('div','erp-collection');host.dataset.erpCollection=candidate.dataset.erpCollection;candidate.removeAttribute('data-erp-collection');candidate.replaceWith(host);host.append(candidate);}if(!host.querySelector(':scope > .erp-table-region')){const region=node('div','erp-table-region');region.append(...host.childNodes);host.append(region);}const table=document.getElementById(host.dataset.erpCollection);if(!table)return;let scheduled=false;const update=()=>{if(scheduled)return;scheduled=true;requestAnimationFrame(()=>{scheduled=false;render(host);});};new MutationObserver(update).observe(table,{subtree:true,childList:true,characterData:true});if(window.jQuery)window.jQuery(table).on('draw.dt processing.dt',update);update();});}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();

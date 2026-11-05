(() => {
 const start=()=>{
  document.querySelectorAll('dialog[data-crm-dialog]').forEach(dialog=>{
   const form=dialog.querySelector('form');const originalAction=form?.getAttribute('action');const title=dialog.querySelector('.erp-dialog__title');const originalTitle=title?.textContent;
   // Menutup dialog (Esc, X, Batal) atau menekan tombol tambah mengembalikan form ke mode tambah.
   const reset=()=>{if(!form)return;form.reset();form.querySelectorAll('select.erp-picker-source').forEach(select=>{[...select.options].forEach(option=>{if(option.value!=='')option.remove();});select.value='';select.dispatchEvent(new Event('change',{bubbles:true}));});form.querySelectorAll('input[name="_method"]').forEach(e=>e.remove());form.setAttribute('action',originalAction);if(title)title.textContent=originalTitle;const error=form.querySelector('[data-crm-form-error]');if(error)error.hidden=true;};
   document.querySelectorAll('[data-crm-create]').forEach(button=>button.addEventListener('click',reset));
   dialog.addEventListener('erp:dialog-close',reset);
   dialog.addEventListener('erp:dialog-open',()=>dialog.querySelector('input:not([type=hidden]):not([type=file]),select')?.focus());
  });
  if(window.jQuery)jQuery(document).ajaxError((event,xhr,settings)=>{
   const modal=document.querySelector('dialog[data-crm-dialog][open]');if(!modal||!settings.type||settings.type.toUpperCase()==='GET')return;
   const box=modal.querySelector('[data-crm-form-error]');if(!box)return;
   const data=xhr.responseJSON||{};box.textContent=Object.values(data.errors||{}).flat().join(' ')||data.message||'Data belum dapat disimpan. Periksa isian lalu coba lagi.';box.hidden=false;box.focus();
  });
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();

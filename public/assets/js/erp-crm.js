(() => {
 const start=()=>{
  document.querySelectorAll('.erp-crm-drawer').forEach(modal=>{
   const form=modal.querySelector('form');const originalAction=form?.getAttribute('action');const title=modal.querySelector('h2');const originalTitle=title?.textContent;
   document.querySelectorAll('[data-crm-create]').forEach(button=>button.addEventListener('click',()=>{if(!form)return;form.reset();form.querySelectorAll('input[name="_method"]').forEach(e=>e.remove());form.setAttribute('action',originalAction);if(title)title.textContent=originalTitle;const error=form.querySelector('[data-crm-form-error]');if(error)error.hidden=true;}));
   modal.querySelector('#kt_modal_add_customer_close')?.addEventListener('click',()=>bootstrap.Modal.getInstance(modal)?.hide());
   modal.addEventListener('shown.bs.modal',()=>modal.querySelector('input:not([type=hidden]):not([type=file]),select')?.focus());
  });
  if(window.jQuery)jQuery(document).ajaxError((event,xhr,settings)=>{
   const modal=document.querySelector('.erp-crm-drawer.show');if(!modal||!settings.type||settings.type.toUpperCase()==='GET')return;
   const box=modal.querySelector('[data-crm-form-error]');if(!box)return;
   const data=xhr.responseJSON||{};box.textContent=Object.values(data.errors||{}).flat().join(' ')||data.message||'Data belum dapat disimpan. Periksa isian lalu coba lagi.';box.hidden=false;box.focus();
  });
 };
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();

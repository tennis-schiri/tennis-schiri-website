/* LK-Einsteiger-Checkliste: nur lokale Browser-Speicherung, kein Benutzerkonto. */
(function(){
 'use strict';
 const STORAGE_KEY='ts-lk-first-tournament-v1';
 const checks=Array.from(document.querySelectorAll('.lk-check[data-id]'));
 const meter=document.getElementById('lk-progress');
 const label=document.getElementById('lk-progress-label');
 const status=document.getElementById('lk-progress-status');
 const reset=document.getElementById('lk-reset');
 if(!checks.length||!meter||!label||!status||!reset)return;
 let saved=[];
 try{
  const raw=JSON.parse(localStorage.getItem(STORAGE_KEY)||'[]');
  if(Array.isArray(raw))saved=raw.filter(x=>typeof x==='string');
 }catch(_){}
 const known=new Set(checks.map(c=>c.dataset.id));
 for(const input of checks)input.checked=saved.includes(input.dataset.id);
 function sync(persist=false){
  const selected=checks.filter(c=>c.checked).map(c=>c.dataset.id).filter(x=>known.has(x));
  for(const input of checks){
   const item=input.closest('.lk-step');
   if(item)item.classList.toggle('is-complete',input.checked);
  }
  const total=checks.length,done=selected.length;
  meter.max=total;meter.value=done;
  label.textContent=done+' von '+total;
  status.textContent=done===0?'Starte mit dem ersten Schritt.':done===total?'Geschafft! Deine Checkliste ist vollständig. Viel Erfolg beim ersten Turnier!':done===total-1?'Nur noch ein Schritt bis zum ersten Turnier!':'Weiter so! '+(total-done)+' Schritte sind noch offen.';
  // Erst nach einer bewussten Checkbox-Aktion speichern, nicht beim Seitenaufruf.
  if(persist){try{localStorage.setItem(STORAGE_KEY,JSON.stringify(selected));}catch(_){}}
 }
 for(const input of checks)input.addEventListener('change',()=>sync(true));
 reset.addEventListener('click',()=>{for(const c of checks)c.checked=false;try{localStorage.removeItem(STORAGE_KEY);}catch(_){}sync();checks[0]?.focus();});
 sync();
})();

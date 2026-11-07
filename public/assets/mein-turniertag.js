/* Mein Turniertag: persönliche Vorbereitung ohne Konto und ohne zweite Turnierdatenbank. */
(function(){
 'use strict';
 const $=id=>document.getElementById(id);
 const STORAGE='ts-mein-turniertag-v1';
 const CHECK_IDS=Array.from(document.querySelectorAll('.mt-check')).map(x=>x.dataset.id);
 const known=new Set(CHECK_IDS);
 const checks=Array.from(document.querySelectorAll('.mt-check'));
 const picker=$('mt-select'), arrival=$('mt-arrival'), match=$('mt-match'), notes=$('mt-notes');
 const eventBox=$('mt-event');
 if(!picker||!arrival||!match||!notes||!checks.length)return;
 let events=[],ready=false;
 const blank=()=>({done:[],arrival:'',match:'',notes:''});
 let state={selected:'',sets:{}};
 try{
  const value=JSON.parse(localStorage.getItem(STORAGE)||'{}');
  if(value&&typeof value==='object'){
    if(typeof value.selected==='string'&&/^[a-z0-9-]{1,100}$/.test(value.selected))state.selected=value.selected;
    if(value.sets&&typeof value.sets==='object'&&!Array.isArray(value.sets))state.sets=value.sets;
  }
 }catch(_){}
 function slot(){return state.selected||'allgemein';}
 function data(){
  const v=state.sets[slot()];
  if(!v||typeof v!=='object')return blank();
  return {
   done:Array.isArray(v.done)?v.done.filter(x=>known.has(x)):[],
   arrival:typeof v.arrival==='string'&&/^([01][0-9]|2[0-3]):[0-5][0-9]$/.test(v.arrival)?v.arrival:'',
   match:typeof v.match==='string'&&/^([01][0-9]|2[0-3]):[0-5][0-9]$/.test(v.match)?v.match:'',
   notes:typeof v.notes==='string'?v.notes.slice(0,1000):''
  };
 }
 function persist(){
  try{localStorage.setItem(STORAGE,JSON.stringify(state));}catch(_){}
 }
 function progress(){
  const done=checks.filter(x=>x.checked).length;
  $('mt-count').textContent=done+' von '+checks.length;
  $('mt-progress').value=done;
  $('mt-progress').max=checks.length;
  $('mt-status').textContent=done===checks.length?'Alles vorbereitet – viel Erfolg auf dem Platz!':done===0?'Los geht’s mit der Vorbereitung.':(checks.length-done)+' Aufgaben stehen noch aus.';
  for(const box of checks)box.closest('.mt-check-item')?.classList.toggle('is-done',box.checked);
 }
 function showEvent(){
  const e=events.find(item=>item.id===state.selected);
  eventBox.hidden=!e;
  if(e){
   $('mt-event-name').textContent=e.title;
   $('mt-event-date').textContent=e.date;
   $('mt-event-venue').textContent=e.place;
   const address=$('mt-event-address');address.textContent=e.address||'';
   address.hidden=!e.address;
   $('mt-event-detail').href='../turniere/termin.html?id='+encodeURIComponent(e.id);
   const route=$('mt-event-route');
   route.hidden=!e.address;
   if(e.address)route.href='https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(e.address);
  }
 }
 function render(){
  const value=data();
  for(const box of checks)box.checked=value.done.includes(box.dataset.id);
  arrival.value=value.arrival;match.value=value.match;notes.value=value.notes;
  showEvent();progress();
 }
 function update(){
  state.sets[slot()]={
   done:checks.filter(x=>x.checked).map(x=>x.dataset.id),
   arrival:arrival.value,match:match.value,notes:notes.value.slice(0,1000)
  };
  persist();progress();
 }
 function readCalendar(html){
  const dom=new DOMParser().parseFromString(html,'text/html');
  return Array.from(dom.querySelectorAll('#tourney-grid > .tourney-card'))
   .filter(card=>!card.classList.contains('workshop-event')&&/^[a-z0-9-]{3,100}$/.test(card.id))
   .map(card=>{
    const ref=Array.from(dom.querySelectorAll('.venue-card')).find(v=>
      Array.from(v.querySelectorAll('.venue-event-list a')).some(a=>{
       const href=a.getAttribute('href')||'';
       return href==='#'+card.id||href==='termin.html?id='+card.id;
      }));
    return {
     id:card.id,
     title:card.querySelector('h3')?.textContent?.trim()||'',
     date:card.querySelector('.datebox')?.getAttribute('aria-label')||'',
     place:ref?.querySelector('h3')?.textContent?.trim()||card.querySelector('.where')?.textContent?.trim()||'',
     address:ref?.querySelector('address')?.textContent?.trim()||''
    };
   }).filter(e=>e.title&&e.date);
 }
 async function load(){
  try{
   const res=await fetch('../turniere/index.html',{credentials:'omit',cache:'no-store'});
   if(!res.ok)throw Error('Turnierkalender nicht erreichbar');
   events=readCalendar(await res.text());
   if(!events.length)throw Error('Keine Turniere vorhanden');
   for(const e of events){
    const option=document.createElement('option');
    option.value=e.id;
    option.textContent=e.date+' · '+e.title;
    picker.append(option);
   }
   const query=new URLSearchParams(location.search).get('turnier')||'';
   if(/^[a-z0-9-]{3,100}$/.test(query)&&events.some(e=>e.id===query))state.selected=query;
   if(state.selected&&!events.some(e=>e.id===state.selected))state.selected='';
   picker.value=state.selected;ready=true;
   $('mt-data-status').textContent='Termine werden aus dem aktuellen Tennis-Schiri-Kalender übernommen. Änderungen bitte im offiziellen Spielplan prüfen.';
   render();
  }catch(_){
   ready=false;
   $('mt-data-status').textContent='Die Turnierauswahl ist gerade nicht verfügbar. Die allgemeine Checkliste kannst du trotzdem benutzen.';
   state.selected='';picker.value='';render();
  }
 }
 picker.addEventListener('change',()=>{
  if(!ready)return;
  state.selected=events.some(e=>e.id===picker.value)?picker.value:'';
  persist();render();
  const url=new URL(location.href);
  if(state.selected)url.searchParams.set('turnier',state.selected);else url.searchParams.delete('turnier');
  history.replaceState(null,'',url.pathname+url.search+url.hash);
 });
 for(const box of checks)box.addEventListener('change',update);
 for(const field of [arrival,match,notes])field.addEventListener('input',update);
 $('mt-print').addEventListener('click',()=>window.print());
 $('mt-reset').addEventListener('click',()=>{
  if(!window.confirm('Alle Häkchen, Uhrzeiten, Notizen und die Turnierauswahl auf diesem Gerät zurücksetzen?'))return;
  try{localStorage.removeItem(STORAGE);}catch(_){}
  state={selected:'',sets:{}};
  picker.value='';
  const url=new URL(location.href);url.searchParams.delete('turnier');
  history.replaceState(null,'',url.pathname+url.search+url.hash);
  render();
 });
 render();
 void load();
})();

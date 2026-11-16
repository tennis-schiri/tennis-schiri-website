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
 function buildPrintSheet(){
  // Print from the live checklist, never from a duplicated static list.
  const mount=$('mt-print-sheet');
  if(!mount)return;
  const el=(tag,cls,value)=>{
   const item=document.createElement(tag);
   if(cls)item.className=cls;
   if(value!==undefined)item.textContent=value;
   return item;
  };
  const title=el('header','mt-print-head');
  title.append(
   el('div','mt-print-brand','TENNIS-SCHIRI.DE  /  MEIN TURNIERTAG'),
   el('h1','','Meine Turniercheckliste'),
   el('span','mt-print-created','Druckstand: '+new Date().toLocaleDateString('de-DE'))
  );
  const event=el('section','mt-print-event');
  event.append(el('span','mt-print-caption','TURNIER'));
  const selected=!eventBox.hidden;
  event.append(el('h2','',selected?$('mt-event-name').textContent:'Allgemeine Checkliste'));
  if(selected){
   const details=[
    $('mt-event-date').textContent,
    $('mt-event-venue').textContent,
    $('mt-event-address').hidden?'':$('mt-event-address').textContent
   ].filter(Boolean);
   event.append(el('p','mt-print-event-meta',details.join('  ·  ')));
  }else{
   event.append(el('p','mt-print-event-meta','Kein Turnier ausgewählt – persönliche Vorbereitung'));
  }
  const schedule=el('div','mt-print-schedule');
  const formatTime=t=>t||'________';
  for(const [label,value] of [
   ['Geplante Ankunft',formatTime(arrival.value)],
   ['Erste Ansetzung',formatTime(match.value)]
  ]){
   const block=el('div','mt-print-schedule-block');
   block.append(el('span','',label),el('strong','',value));
   schedule.append(block);
  }
  const notesWrap=el('section','mt-print-notes');
  notesWrap.append(el('strong','','Notizen'));
  const full=notes.value.trim().replace(/\s+/g,' ');
  const shortened=full.length>190?full.slice(0,187).trimEnd()+' … (gekürzt)':full;
  notesWrap.append(el('p','',shortened||'______________________________________________________________________'));
  const checklistTitle=el('div','mt-print-checks-title');
  checklistTitle.append(el('h2','','15 Punkte für deinen Turniertag'));
  checklistTitle.append(el('span','','4 Phasen  ·  zum Abhaken'));
  const grid=el('div','mt-print-grid');
  const sections=Array.from(document.querySelectorAll('.mt-check-section'));
  for(const section of sections){
   const block=el('section','mt-print-phase');
   const heading=el('div','mt-print-phase-heading');
   heading.append(
    el('span','mt-print-number',section.querySelector('.mt-step-no')?.textContent||''),
    el('h3','',section.querySelector('h3')?.textContent||'')
   );
   const items=el('div','mt-print-items');
   for(const label of section.querySelectorAll('.mt-check-item')){
    const input=label.querySelector('input.mt-check');
    const line=el('div','mt-print-item');
    const box=el('span','mt-print-box'+(input?.checked?' is-checked':''),input?.checked?'✓':'');
    box.setAttribute('aria-hidden','true');
    line.append(box,el('span','',label.querySelector('span')?.textContent||''));
    items.append(line);
   }
   block.append(heading,items);
   grid.append(block);
  }
  const caution=el('div','mt-print-footer',
   'Wichtig: Maßgeblich bleiben Ausschreibung, Ansetzungen und Hinweise der Turnierleitung.  ·  tennis-schiri.de');
  mount.replaceChildren(title,event,schedule,notesWrap,checklistTitle,grid,caution);
 }
 // Browser print via button, keyboard shortcut or mobile share dialog.
 window.addEventListener('beforeprint',buildPrintSheet);
 if(window.matchMedia){
  const printMedia=window.matchMedia('print');
  if(printMedia.addEventListener)printMedia.addEventListener('change',e=>{if(e.matches)buildPrintSheet();});
 }
 $('mt-print').addEventListener('click',()=>{buildPrintSheet();window.print();});
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

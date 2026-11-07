/* Tennis-Schiri dynamic tournament listing: Berlin-local dates, archive, weather, approved public notes. */
(function () {
 'use strict';
 const TZ='Europe/Berlin';
 const dayBerlin=()=>new Intl.DateTimeFormat('sv-SE',{timeZone:TZ,year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
 const clockBerlin=()=>new Intl.DateTimeFormat('sv-SE',{timeZone:TZ,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false}).format(new Date()).replace(' ','T');
 const endpoint='/data/newsletter-events.json';
 async function getJson(path){const r=await fetch(path,{credentials:'omit'});if(!r.ok)throw new Error('HTTP '+r.status);return r.json();}
 const text=(el,selector)=>el.querySelector(selector)?.textContent?.trim()||'';
 function dataForCard(card,events) {
  if(card.id)return events.find(e=>e.id===card.id);
  const name=text(card,'.event-title');
  const place=text(card,'.event-where');
  return events.find(e=>e.type==='turnier'&&e.date_start===card.dataset.start&&e.title===name&&e.location===place);
 }
 function drawTime(card){
  const details=Array.from(card.querySelectorAll('.tournament-detail'));
  const row=details.find(d=>text(d,'dt')==='Auslosung');
  const label=row?text(row,'dd'):'';
  const m=label.match(/(\d{1,2})\.(\d{1,2})\.(20\d{2}),?\s*(\d{2}:\d{2})/);
  if(!m)return null;
  return m[3]+'-'+m[2].padStart(2,'0')+'-'+m[1].padStart(2,'0')+'T'+m[4];
 }
 function isCurrent(item,now,clock) {
  if((item.end||item.start)<now)return false;
  if(item.start<=now)return true;
  const draw=drawTime(item.node);
  return Boolean(draw&&clock>=draw);
 }
 function actionLink(href,label,className){
  const a=document.createElement('a');a.href=href;a.textContent=label;
  a.className=className;a.rel='noopener noreferrer';
  return a;
 }
 async function publicInfoLinks(nodes){
  let ids=[];
  try{const d=await getJson('/turnierinfos.php?status=1');if(Array.isArray(d.published))ids=d.published;}catch(_){}
  const available=new Set(ids);
  for(const {node,id} of nodes){
   if(!id||!available.has(id))continue;
   const actions=node.querySelector('.tourney-actions,.event-actions');
   if(!actions||actions.querySelector('.public-info-link'))continue;
   actions.appendChild(actionLink('/turnierinfos.php?turnier='+encodeURIComponent(id),'📋 Turnierinfos ansehen','public-info-link'));
  }
 }
 async function addWeather(node,event){
  if(!event?.outdoor || !event.weather_region || !event.id)return;
  const actions=node.querySelector('.tourney-actions,.event-actions')||node;
  if(actions.querySelector('.tourney-weather'))return;
  const today=dayBerlin();
  const start=event.date_start,end=event.date_end||start;
  // Calendar weather only in the 7-day window, never on past events.
  const todayMid=new Date(today+'T12:00:00Z');
  const startMid=new Date(start+'T12:00:00Z');
  const distance=Math.round((startMid-todayMid)/86400000);
  if(end<today||distance>7)return;
  const slot=document.createElement('div');slot.className='tourney-weather';slot.setAttribute('aria-live','polite');
  if(actions===node)node.appendChild(slot);else actions.after(slot);
  try{
   const data=await getJson('/tournament-weather.php?id='+encodeURIComponent(event.id));
   if(!data.available)return;
   const p=data.forecast;
   const info=[];
   if(p){
    info.push('🌦️ '+p.description+' · '+p.temperature_min+'–'+p.temperature_max+' °C');
    if(p.precipitation_probability!==null)info.push('Regen: '+p.precipitation_probability+' %');
   }
   if(data.sunset)info.push('🌇 Sonnenuntergang '+data.sunset+' Uhr');
   if(!info.length)return;
   const line=document.createElement('p');line.textContent=info.join(' · ');
   slot.appendChild(line);
   const source=document.createElement('small');
   source.textContent='Unverbindliche '+(p?'Wetterprognose':'Sonnenuntergangszeit')+' für '+data.location+' · ';
   const a=actionLink('https://brightsky.dev/','DWD / Bright Sky','');a.target='_blank';
   source.appendChild(a);slot.appendChild(source);
  }catch(_){slot.remove();}
 }
 async function homepage(events){
  const raw=Array.from(document.querySelectorAll('#event-list .tournament-row'));
  const featured=document.querySelector('.featured-tournament');
  if(!featured||!raw.length)return;
  const today=dayBerlin(),clock=clockBerlin();
  const items=raw.map(node=>{
    const event=dataForCard(node,events);
    return {node,event,id:event?.id||'',start:node.dataset.start,end:event?.date_end||node.dataset.start,type:node.dataset.type};
  }).sort((a,b)=>a.start.localeCompare(b.start));
  const eligible=items.filter(i=>i.end>=today);
  const active=eligible.find(i=>isCurrent(i,today,clock));
  const nearest=active||eligible[0];
  for(const item of items){
   item.node.classList.toggle('featured-hidden',item===nearest);
   item.node.classList.toggle('event-past',item.end<today);
  }
  function refresh(){
   const filter=document.querySelector('.filter.active')?.dataset.filter||'alle';
   let shown=0;
   for(const item of items){
    const show=item!==nearest&&item.end>=today&&(filter==='alle'||item.type===filter)&&shown<4;
    item.node.classList.toggle('home-excess',!show);
    if(show)shown++;
   }
   const summary=document.getElementById('home-tourney-summary');
   if(summary)summary.textContent=shown===0?'Keine weiteren passenden Turniere angekündigt':shown===1?'1 weiteres Turnier':shown+' weitere Turniere';
  }
  window.refreshHomepageTournaments=refresh;
  refresh();
  if(!nearest){
   featured.hidden=true;
   const plate=document.querySelector('.hero-plate');
   if(plate)plate.hidden=true;
   return;
  }
  const node=nearest.node, date=node.querySelector('.datebox');
  const first=text(date,'.day'),last=text(date,'.day-end'),month=text(date,'.month'),year=text(date,'.year');
  const dateLabel=last?first+'.–'+last+'.':first+'.';
  const fullDate=dateLabel+' '+(year?month+' '+year:month);
  const current=Boolean(active&&nearest===active),prefix=current?'AKTUELLES TURNIER':'NÄCHSTES TURNIER';
  featured.dataset.start=nearest.start;
  featured.setAttribute('aria-label',current?'Aktuelles Turnier':'Nächstes Turnier');
  featured.querySelector('.featured-kicker').textContent=prefix+' · '+text(date,'.weekday')+' · '+fullDate;
  featured.querySelector('h3').textContent=text(node,'.event-title');
  featured.querySelector('.featured-location').textContent=text(node,'.event-where');
  featured.querySelector('.tournament-meta').innerHTML=node.querySelector('.tournament-meta')?.innerHTML||'';
  featured.querySelector('.tournament-details').innerHTML=node.querySelector('.tournament-details')?.innerHTML||'';
  featured.querySelector('.tournament-cta,.tournament-soon')?.remove();
  const action=node.querySelector('.event-actions')?.firstElementChild?.cloneNode(true);
  if(action)featured.querySelector('.featured-location').parentElement.appendChild(action);
  const box=featured.querySelector('.featured-date');box.innerHTML=date.innerHTML;
  box.classList.toggle('datebox-range',date.classList.contains('datebox-range'));
  box.setAttribute('aria-label',date.getAttribute('aria-label')||'');
  const plate=document.querySelector('.hero-plate');
  if(plate){
   const small=plate.querySelector('.small');
   if(small){
    Array.from(small.childNodes).filter(n=>n.nodeType===3).forEach(n=>n.textContent='');
    small.appendChild(document.createTextNode(prefix.toLowerCase().replace(/^\w/,m=>m.toUpperCase())));
   }
   plate.querySelector('.date').textContent=text(date,'.weekday')+' · '+fullDate;
   plate.querySelector('.hero-plate-title').textContent=text(node,'.event-title');
   plate.querySelector('.hero-plate-place').textContent=text(node,'.event-where');
  }
  const targets=eligible.slice(0,6).map(i=>({node:i.node,id:i.id}));
  targets.push({node:featured,id:nearest.id});
  await publicInfoLinks(targets);
  // The featured card is a separate presentation of the same event.
  const copy=featured.querySelector('.tourney-actions,.event-actions');
  if(!copy&&nearest.id){
    const published=nearest.node.querySelector('.public-info-link');
    if(published&&!featured.querySelector('.public-info-link'))featured.appendChild(published.cloneNode(true));
  }
  for(const item of eligible.slice(0,5))void addWeather(item.node,item.event);
  if(nearest.event)void addWeather(featured,nearest.event);
 }
 function makeButton(label,page,active){
  const b=document.createElement('button');b.type='button';b.textContent=label;
  b.className='tourney-page-btn'+(active?' is-active':'');b.dataset.page=String(page);
  if(active)b.setAttribute('aria-current','page');
  return b;
 }
 async function listing(events){
  const grid=document.getElementById('tourney-grid');
  const pager=document.getElementById('tourney-pagination');
  const controls=document.getElementById('tourney-page-controls');
  const status=document.getElementById('tourney-page-status');
  if(!grid||!pager||!controls||!status)return;
  const all=Array.from(grid.querySelectorAll(':scope > .tourney-card'));
  const today=dayBerlin(),clock=clockBerlin();
  const enriched=all.map(node=>{
   const event=events.find(e=>e.id===node.id);
   return {node,id:node.id,event,start:event?.date_start||'9999-12-31',end:event?.date_end||event?.date_start||'9999-12-31'};
  });
  const upcoming=enriched.filter(x=>x.end>=today).sort((a,b)=>a.start.localeCompare(b.start));
  const past=enriched.filter(x=>x.end<today).sort((a,b)=>b.end.localeCompare(a.end));
  // An older hardcoded "NÄCHSTES TURNIER" label must never survive in the archive.
  for(const e of enriched){
   const kicker=e.node.querySelector('.eyebrow.kicker');
   if(kicker&&/NÄCHSTES TURNIER|AKTUELLES TURNIER/.test(kicker.textContent||''))kicker.remove();
  }
  const current=upcoming.find(e=>isCurrent(e,today,clock));
  const lead=current||upcoming[0];
  if(lead){
   const b=document.createElement('div');b.className='eyebrow kicker current-tourney-label';
   b.textContent=current&&lead===current?'AKTUELLES TURNIER':'NÄCHSTES TURNIER';
   lead.node.querySelector('h3')?.before(b);
  }
  for(const e of past){
   const cta=e.node.querySelector('.tournament-cta');
   if(cta)cta.textContent='Vergangenes Turnier ansehen ↗';
   const soon=e.node.querySelector('.tournament-soon');
   if(soon)soon.textContent='Abgeschlossen';
  }
  // Archive stays visible in the DOM and under its original stable anchor.
  const archive=document.createElement('details');archive.id='turnier-archiv';archive.className='tourney-archive';
  const summary=document.createElement('summary');summary.textContent='Vergangene Turniere ('+past.length+') · Archiv anzeigen';
  archive.appendChild(summary);
  const oldGrid=document.createElement('div');oldGrid.className='tourney-grid';archive.appendChild(oldGrid);
  for(const e of past){e.node.classList.add('archived-tourney');oldGrid.appendChild(e.node);}
  pager.after(archive);archive.hidden=past.length===0;
  if(!upcoming.length){
   const empty=document.createElement('p');empty.className='info-note';empty.textContent='Aktuell sind keine weiteren Turniere angekündigt. Vergangene Termine findest du im Archiv.';
   grid.appendChild(empty);
  }
  const pageSize=5,totalPages=Math.max(1,Math.ceil(upcoming.length/pageSize));
  let current=1;
  function pageFromHash(){
    let h='';try{h=decodeURIComponent(location.hash.slice(1));}catch(_){return null;}
    const pastItem=past.find(e=>e.id===h);
    if(pastItem){archive.open=true;return {past:pastItem};}
    const i=upcoming.findIndex(e=>e.id===h);
    return i<0?null:{page:Math.floor(i/pageSize)+1,element:upcoming[i].node};
  }
  function fromQuery(){
   const raw=new URLSearchParams(location.search).get('seite');
   const n=raw&&/^\d+$/.test(raw)?Number(raw):1;
   return Math.min(totalPages,Math.max(1,n));
  }
  function render(page,update=false,scroll=false,hash=''){
   current=Math.min(totalPages,Math.max(1,page));
   const first=(current-1)*pageSize;
   for(const [i,e] of upcoming.entries())e.node.hidden=i<first||i>=first+pageSize;
   status.textContent=upcoming.length?'Turniere '+(first+1)+'–'+Math.min(upcoming.length,first+pageSize)+' von '+upcoming.length:'Keine kommenden Turniere';
   controls.replaceChildren();
   const prev=makeButton('‹ Zurück',current-1,false);prev.disabled=current===1;controls.appendChild(prev);
   for(let p=1;p<=totalPages;p++)controls.appendChild(makeButton(String(p),p,p===current));
   const next=makeButton('Weiter ›',current+1,false);next.disabled=current===totalPages;controls.appendChild(next);
   pager.hidden=totalPages<=1;
   if(update){
    const url=new URL(location.href);
    if(current===1)url.searchParams.delete('seite');else url.searchParams.set('seite',String(current));
    url.hash=hash||'';history.pushState({tourneyPage:current},'',url.pathname+url.search+url.hash);
   }
   if(scroll)document.getElementById('termine')?.scrollIntoView({behavior:'smooth',block:'start'});
  }
  function revealHash(){
   const target=pageFromHash();
   if(target?.past){target.past.node.scrollIntoView({block:'start'});return;}
   render(target?.page||fromQuery(),false,false);
   if(target?.element)requestAnimationFrame(()=>target.element.scrollIntoView({block:'start'}));
  }
  controls.addEventListener('click',ev=>{
   const b=ev.target.closest('button[data-page]');if(!b||b.disabled)return;
   render(Number(b.dataset.page),true,true);
  });
  document.querySelectorAll('.venue-event-list a[href^="#turnier-"]').forEach(a=>{
   a.addEventListener('click',ev=>{
    const id=a.getAttribute('href').slice(1);
    const old=past.find(e=>e.id===id);
    if(old){ev.preventDefault();archive.open=true;history.pushState(null,'','#'+id);old.node.scrollIntoView({block:'start'});return;}
    const i=upcoming.findIndex(e=>e.id===id);if(i<0)return;
    ev.preventDefault();render(Math.floor(i/pageSize)+1,true,false,'#'+id);
    upcoming[i].node.scrollIntoView({block:'start'});
   });
  });
  window.addEventListener('hashchange',revealHash);window.addEventListener('popstate',revealHash);
  revealHash();
  await publicInfoLinks(enriched.map(e=>({node:e.node,id:e.id})));
  // Weather chips only from seven days before the first day through the final day.
  for(const item of upcoming)void addWeather(item.node,item.event);
 }
 async function start(){
  const home=document.getElementById('event-list');
  const list=document.getElementById('tourney-grid');
  if(!home&&!list)return;
  let catalog=[];
  try{const data=await getJson(endpoint);if(Array.isArray(data.events))catalog=data.events.filter(e=>e.type==='turnier');}
  catch(err){console.warn('Turnierkatalog nicht verfügbar',err);}
  if(home)await homepage(catalog);
  if(list)await listing(catalog);
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});
 else void start();
})();

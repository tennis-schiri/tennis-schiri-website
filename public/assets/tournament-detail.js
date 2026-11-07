/* Detailseite: liest alle Angaben direkt aus der bestehenden Turnierliste.
   Es wird kein zweiter Turnierkatalog benötigt. */
(function(){
 'use strict';
 const $=id=>document.getElementById(id);
 const MONTHS={'januar':1,'februar':2,'märz':3,'maerz':3,'april':4,'mai':5,'juni':6,'juli':7,'august':8,'september':9,'oktober':10,'november':11,'dezember':12};
 const VALUE=el=>el?.textContent?.trim()||'';
 const newEl=(tag,text,className)=>{
  const el=document.createElement(tag);
  if(text!==undefined)el.textContent=text;
  if(className)el.className=className;
  return el;
 };
 function parseDate(label){
  const year=label.match(/\b(20\d{2})\b/);
  const matches=Array.from(label.matchAll(/(\d{1,2})\.\s+([a-zA-ZäöüÄÖÜß]+)/g));
  if(!year||!matches.length)return null;
  const parsed=matches.map(m=>{
   const month=MONTHS[m[2].toLocaleLowerCase('de-DE')];
   if(!month)return null;
   const day=Number(m[1]);
   const date=new Date(Date.UTC(Number(year[1]),month-1,day,12));
   if(date.getUTCDate()!==day||date.getUTCMonth()!==month-1)return null;
   return date.toISOString().slice(0,10);
  });
  if(parsed.some(x=>!x))return null;
  return {start:parsed[0],end:parsed[parsed.length-1],label};
 }
 function nextDay(date){
  const d=new Date(date+'T12:00:00Z');
  d.setUTCDate(d.getUTCDate()+1);
  return d.toISOString().slice(0,10);
 }
 function compact(date){return date.replaceAll('-','');}
 function safeIcs(value){return String(value).replace(/\\/g,'\\\\').replace(/\r?\n/g,'\\n').replace(/,/g,'\\,').replace(/;/g,'\\;');}
 function mapsUrl(address){return 'https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(address);}
 function officialLogo(venue,location){
  if(/bad saarow/i.test(venue+' '+location))return {src:'../assets/partner/sunball-tenniscenter.png',alt:'SUNBALL Tennis Center Bad Saarow'};
  return null;
 }
 function row(title,value){
  const wrap=document.createElement('div');
  wrap.append(newEl('dt',title),newEl('dd',value));
  $('event-facts').append(wrap);
 }
 function addAction(parent,href,label,light=false,external=false){
  const a=newEl('a',label,light?'tourney-detail-cta-light':'');
  a.href=href;
  if(external){a.target='_blank';a.rel='noopener noreferrer';}
  parent.appendChild(a);return a;
 }
 function renderMap(address){
  const gate=$('event-map-gate');
  const area=$('event-map');
  if(!address){
   gate.replaceChildren(newEl('p','Für diesen Termin liegt noch keine genaue Anschrift vor. Die Karte wird ergänzt, sobald der Veranstaltungsort bestätigt ist.'));
   return;
  }
  addAction($('event-directions'),mapsUrl(address),'Anfahrt mit Google Maps ↗',true,true);
  const loadMap=()=>{
   if(area.querySelector('iframe'))return;
   const iframe=document.createElement('iframe');
   iframe.title='Google-Maps-Karte: '+address;
   iframe.loading='lazy';
   iframe.referrerPolicy='no-referrer-when-downgrade';
   iframe.src='https://maps.google.com/maps?output=embed&q='+encodeURIComponent(address);
   iframe.setAttribute('allowfullscreen','');
   area.replaceChildren(iframe);
  };
  $('event-load-map').addEventListener('click',()=>{
   // Ohne Einwilligung ist ein explizites Einmal-Opt-in erforderlich.
   if(window.TSPrivacy?.requestMap)window.TSPrivacy.requestMap(loadMap);
   else gate.append(newEl('p','Datenschutzeinstellungen nicht geladen. Bitte die Seite aktualisieren.'));
  });
  window.addEventListener('ts:privacy-change',event=>{
   // Bei Widerruf eine bereits geladene externe Karte aus dem DOM entfernen.
   if(event.detail?.maps===false&&area.querySelector('iframe'))area.replaceChildren(gate);
  });
 }
 function installCalendar(event){
  const info=[event.date.label,event.venue,event.address,event.meta.join(' · '),...event.details.map(d=>d[0]+': '+d[1])].filter(Boolean).join('\n');
  const url='https://tennis-schiri.de/turniere/termin.html?id='+encodeURIComponent(event.id);
  const isWorkshop=event.type==='workshop';
  const stamp=new Date().toISOString().replace(/[-:]/g,'').replace(/\.\d{3}/,'');
  const start=isWorkshop&&event.time?
   'DTSTART;TZID=Europe/Berlin:'+compact(event.date.start)+'T'+event.time.replace(':','')+'00':
   'DTSTART;VALUE=DATE:'+compact(event.date.start);
  const end=isWorkshop&&event.time?'':'DTEND;VALUE=DATE:'+compact(nextDay(event.date.end));
  const lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Tennis-Schiri//Turnierkalender//DE','CALSCALE:GREGORIAN','METHOD:PUBLISH','BEGIN:VEVENT','UID:'+event.id+'@tennis-schiri.de','DTSTAMP:'+stamp,start];
  if(end)lines.push(end);
  lines.push('SUMMARY:'+safeIcs(event.title),'LOCATION:'+safeIcs(event.address||event.location),'DESCRIPTION:'+safeIcs(info+'\nAlle Angaben: '+url),'URL:'+url,'END:VEVENT','END:VCALENDAR');
  $('event-download-ics').addEventListener('click',()=>{
   const content=lines.join('\r\n')+'\r\n';
   const object=URL.createObjectURL(new Blob([content],{type:'text/calendar;charset=utf-8'}));
   const link=document.createElement('a');link.href=object;link.download='tennis-schiri-'+event.id+'.ics';
   document.body.appendChild(link);link.click();link.remove();
   setTimeout(()=>URL.revokeObjectURL(object),30000);
  });
  const google=$('event-google-calendar');
  if(isWorkshop&&event.time){
   $('calendar-note').textContent='Beginn: '+event.time+' Uhr. Eine Endzeit ist nicht angegeben. Die .ics-Datei übernimmt den bekannten Beginn.';
   google.hidden=true; // Kein erfundenes Ende für einen Google-Kalendereintrag
  }else{
   $('calendar-note').textContent='Keine Startzeit veröffentlicht – der Termin wird ganztägig übernommen.';
   const fields=new URLSearchParams({
    action:'TEMPLATE',text:event.title,
    dates:compact(event.date.start)+'/'+compact(nextDay(event.date.end)),
    details:'Weitere Infos: '+url,
    location:event.address||event.location
   });
   google.href='https://calendar.google.com/calendar/render?'+fields.toString();
  }
 }
 async function showPublicInfo(id){
  try{
   const r=await fetch('../turnierinfos.php?status=1',{credentials:'omit'});
   if(!r.ok)return;
   const data=await r.json();
   if(Array.isArray(data.published)&&data.published.includes(id)){
    addAction($('event-registration'),'../turnierinfos.php?turnier='+encodeURIComponent(id),'Infos der Turnierleitung ↗',true);
   }
  }catch(_){}
 }
 async function start(){
  const id=new URLSearchParams(location.search).get('id')||'';
  if(!/^[a-z0-9-]{3,100}$/.test(id)){$('event-error').hidden=false;return;}
  try{
   const response=await fetch('./index.html',{credentials:'omit',cache:'no-store'});
   if(!response.ok)throw new Error('Katalog nicht verfügbar');
   const doc=new DOMParser().parseFromString(await response.text(),'text/html');
   const card=Array.from(doc.querySelectorAll('#tourney-grid > .tourney-card')).find(c=>c.id===id);
   if(!card)throw new Error('Veranstaltung unbekannt');
   const title=VALUE(card.querySelector('h3'));
   const place=VALUE(card.querySelector('.where'));
   const aria=card.querySelector('.datebox')?.getAttribute('aria-label')||'';
   const date=parseDate(aria);
   if(!title||!date)throw new Error('Datumsangaben fehlen');
   const workshop=card.classList.contains('workshop-event');
   const venueCard=Array.from(doc.querySelectorAll('.venue-card')).find(v=>Array.from(v.querySelectorAll('.venue-event-list a')).some(a=>{const href=a.getAttribute('href')||'';return href==='#'+id||href==='termin.html?id='+id;}));
   const venue=venueCard?VALUE(venueCard.querySelector('h3')):place;
   const address=venueCard?VALUE(venueCard.querySelector('address')):'';
   const meta=Array.from(card.querySelectorAll('.meta-chip')).map(VALUE);
   const details=Array.from(card.querySelectorAll('.tournament-detail')).map(n=>[VALUE(n.querySelector('dt')),VALUE(n.querySelector('dd'))]).filter(pair=>pair[0]&&pair[1]);
   const description=VALUE(card.querySelector('.workshop-event-description'));
   const time=workshop?(place.match(/(\d{1,2}:\d{2})\s*Uhr/)||[])[1]||'':'';
   const event={id,title,location:place,date,venue,address,meta,details,type:workshop?'workshop':'turnier',time};
   document.title=title+' | Termine | Tennis-Schiri';
   $('event-name').textContent=title;
   $('event-subtitle').textContent=aria+' · '+venue;
   $('event-type').textContent=workshop?'REGELWORKSHOP · EVENT':'TURNIER · TERMINE';
   $('event-date').textContent=aria+(time?' · Beginn '+time+' Uhr':'');
   for(const item of meta)$('event-chips').appendChild(newEl('span',item));
   row('Termin',aria);
   if(time)row('Beginn',time+' Uhr');
   row('Veranstaltungsort',venue);
   if(address)row('Anschrift',address);
   for(const pair of details)row(pair[0],pair[1]);
   if(description){$('event-description').textContent=description;$('event-description').hidden=false;}
   $('event-venue').textContent=venue;
   $('event-address').textContent=address||'Anschrift noch nicht veröffentlicht';
   const img=officialLogo(venue,place);
   if(img){
    const image=document.createElement('img');image.src=img.src;image.alt=img.alt;image.loading='lazy';
    $('event-logo').replaceChildren(image);
    $('event-logo').hidden=false;
   }
   const registration=card.querySelector('.tournament-cta');
   if(registration){
    const destination=new URL(registration.getAttribute('href'),new URL('./index.html',location.href)).href;
    const external=destination.startsWith('https://')&&!destination.startsWith(location.origin+'/');
    addAction($('event-registration'),destination,VALUE(registration),false,external);
   }else{
    $('event-registration').appendChild(newEl('span',VALUE(card.querySelector('.tournament-soon'))||'Weitere Informationen folgen','tournament-soon'));
   }
   renderMap(address);
   installCalendar(event);
   $('event-content').hidden=false;
   if(!workshop)void showPublicInfo(id);
  }catch(err){
   console.warn('Turnierdetails nicht verfügbar',err);
   $('event-error').hidden=false;
  }
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else void start();
})();

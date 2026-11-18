/* Tennis-Schiri: web presenter enhancement + synchronized room host */
'use strict';
(function(){
  const payload=JSON.parse(document.getElementById('workshop-data')?.textContent||'{}');
  const slides=payload.slides||[];
  const frame=document.getElementById('frame');
  const image=document.getElementById('slide-image');
  const toolbar=document.querySelector('.toolbar');
  if(!frame||!image||!toolbar||!slides.length||!window.TSWorkshopSlide)return;
  const stage=document.createElement('section');stage.id='workshop-stage';stage.className='workshop-stage';stage.setAttribute('aria-live','polite');
  frame.insertBefore(stage,image);
  let original=false,room=null,hostToken=null,latestVotes=null,lastBroadcast=0;
  const index=()=>Math.max(1,Math.min(136,Number(location.hash.match(/folie=(\d+)/)?.[1])||1));
  const current=()=>slides[index()-1];
  const showRich=()=>{
    const s=current();
    stage.querySelectorAll('video').forEach(x=>x.pause());
    window.TSWorkshopSlide.render(stage,s,{onOriginal:()=>{
      original=true;stage.hidden=true;frame.classList.remove('rich-enabled');
      const back=document.getElementById('original-close');
      if(back)back.hidden=false;
    },isHost:!!room,votes:room&&latestVotes||null});
    if(!original){stage.hidden=false;frame.classList.add('rich-enabled')}
  };
  const originalClose=document.createElement('button');
  originalClose.id='original-close';originalClose.type='button';originalClose.className='original-close';
  originalClose.textContent='Zur neuen Folienansicht';originalClose.hidden=true;
  originalClose.onclick=()=>{original=false;originalClose.hidden=true;showRich()};
  frame.append(originalClose);
  function changed(){
    original=false;originalClose.hidden=true;latestVotes=null;showRich();
    if(room&&hostToken)broadcast(index());
  }
  new MutationObserver(changed).observe(image,{attributes:true,attributeFilter:['src']});
  showRich();
  const live=document.createElement('button');live.id='host-live-button';live.type='button';live.textContent='● Live starten';
  live.title='Teilnehmer mit Raumcode verbinden und Regelfragen abstimmen lassen';
  toolbar.insertBefore(live,document.getElementById('full-btn'));
  const panel=document.createElement('div');panel.className='host-room-panel';panel.hidden=true;
  panel.innerHTML='<section class="host-room-box" role="dialog" aria-modal="false" aria-labelledby="host-room-title"><button class="host-room-dismiss" aria-label="Schließen">✕</button><p class="host-room-kicker">LIVE · TEILNEHMER</p><h2 id="host-room-title">Verbinde deine Gruppe.</h2><p>Die Teilnehmer öffnen den Link und geben ihr Workshop-Passwort ein. Sie sehen automatisch die aktuelle Folie und können bei Regelfragen abstimmen.</p><strong class="host-room-code" id="host-room-code">······</strong><p class="host-room-link" id="host-room-link"></p><div class="host-room-actions"><button id="host-room-copy">Teilnahmelink kopieren</button><button id="host-room-stop">Live-Raum beenden</button></div><p class="host-room-status" id="host-room-status"></p></section>';
  document.body.appendChild(panel);
  const dismiss=()=>panel.hidden=true;
  panel.querySelector('.host-room-dismiss').onclick=dismiss;
  panel.onclick=e=>{if(e.target===panel)dismiss()};
  const call=async(op,data,method='POST')=>{
    const url='/live.php?op='+encodeURIComponent(op);
    const res=await fetch(url,{method,credentials:'same-origin',cache:'no-store',
      headers:{'Content-Type':'application/json','Accept':'application/json'},
      body:method==='POST'?JSON.stringify(data):undefined});
    const obj=await res.json();if(!res.ok||!obj.ok)throw Error(obj.error||'Live-Raum nicht erreichbar');return obj;
  };
  const joinUrl=()=>new URL('/teilnehmen.php?room='+room,location.href).href;
  function setLiveStatus(text){panel.querySelector('#host-room-status').textContent=text}
  function showPanel(){
    panel.hidden=false;panel.querySelector('#host-room-code').textContent=room||'······';
    panel.querySelector('#host-room-link').textContent=room?joinUrl():'';
    setLiveStatus(room?'Raum ist aktiv. Der Folienwechsel wird live übertragen.':'Raum wird vorbereitet …');
  }
  async function broadcast(slide){
    if(!room||!hostToken)return;
    const token=++lastBroadcast;
    try{
      const data=await call('slide',{room,slide,host_token:hostToken});
      if(token===lastBroadcast&&data.slide===index())latestVotes=data.votes;
    }catch(e){setLiveStatus('Verbindung unterbrochen: '+e.message)}
  }
  live.onclick=async()=>{
    if(room){showPanel();return}
    live.disabled=true;panel.hidden=false;setLiveStatus('Live-Raum wird angelegt ...');
    try{
      const data=await call('create',{slide:index()});
      room=data.room;hostToken=data.host_token;live.textContent='● Live: '+room;
      live.classList.add('is-live');showPanel();
    }catch(e){setLiveStatus('Fehler: '+e.message)}finally{live.disabled=false}
  };
  panel.querySelector('#host-room-copy').onclick=async()=>{
    if(!room)return;
    const txt=joinUrl();
    try{await navigator.clipboard.writeText(txt);setLiveStatus('Link kopiert. Den Raumcode siehst du oben.')}
    catch(e){setLiveStatus('Bitte den oben angezeigten Link markieren und kopieren.')}
  };
  panel.querySelector('#host-room-stop').onclick=async()=>{
    if(!room)return;
    try{await call('end',{room,host_token:hostToken})}
    catch(e){setLiveStatus(e.message);return}
    room=null;hostToken=null;latestVotes=null;live.textContent='● Live starten';live.classList.remove('is-live');dismiss();
  };
  setInterval(async()=>{
    if(!room||!hostToken||document.hidden)return;
    try{
      const res=await fetch('/live.php?op=status&room='+room,{credentials:'same-origin',cache:'no-store'});
      if(!res.ok)return;const d=await res.json();
      if(!d.active)return;
      latestVotes=d.votes;
      const tally=stage.querySelector('.rw-tally');
      if(current().type==='question'&&tally)tally.textContent='Live-Abstimmung: '+d.total+' Stimmen · A '+(d.votes.A||0)+' / B '+(d.votes.B||0)+' / C '+(d.votes.C||0);
      else if(current().type==='question'&&d.total)showRich();
    }catch(e){}
  },3500);
  // Eine sofort sichtbare Abstimmungsfrage statt nur Frontalunterricht.
  document.addEventListener('visibilitychange',()=>{if(!document.hidden&&room)broadcast(index())});
  window.__workshopLiveHost={getRoom:()=>room,show:showPanel};
})();

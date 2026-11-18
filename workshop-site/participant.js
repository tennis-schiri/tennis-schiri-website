/* © Tennis-Schiri · mobiler Workshopteilnehmer, Live-Folien und Abstimmungen */
'use strict';
(async function(){
  const stage=document.getElementById('participant-stage');
  const form=document.getElementById('room-form');
  const input=document.getElementById('room-code');
  const connect=document.getElementById('connect-status');
  const followButton=document.getElementById('follow-btn');
  const counter=document.getElementById('participant-counter');
  const prev=document.getElementById('participant-prev');
  const next=document.getElementById('participant-next');
  if(!stage||!window.TSWorkshopSlide)return;
  let slides=[],room=null,slide=1,remoteSlide=1,following=true,voted={};
  let currentStatus=null,loading=false,originalView=false;
  const setStatus=(text,type)=>{connect.textContent=text;connect.dataset.status=type||''};
  const call=async(url,options)=>{
    const response=await fetch(url,Object.assign({credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}},options||{}));
    const result=await response.json();
    if(!response.ok||!result.ok)throw Error(result.error||'Verbindung fehlgeschlagen');
    return result;
  };
  const resetVideos=()=>{stage.querySelectorAll('video').forEach(v=>{v.pause();v.removeAttribute('src');v.load()})};
  const render=()=>{
    if(!slides.length)return;
    const s=slides[slide-1];if(!s)return;
    resetVideos();stage.replaceChildren();
    if(originalView){
      const box=document.createElement('div');box.className='participant-original-view';
      const back=document.createElement('button');back.textContent='← Moderne Ansicht';back.onclick=()=>{originalView=false;render()};
      const img=document.createElement('img');img.src='slides/slide-'+String(s.no).padStart(3,'0')+'.webp';
      img.alt='Originalansicht Folie '+s.no+': '+s.title;
      box.append(back,img);stage.append(box);
    }else{
      const myVote=voted[(room||'solo')+':'+slide];
      window.TSWorkshopSlide.render(stage,s,{
        voted:myVote,
        onVote:async(answer)=>{
          const key=(room||'solo')+':'+slide;
          if(voted[key])return;
          if(room&&currentStatus?.active){
            try{
              await call('/live.php?op=vote',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},
                body:JSON.stringify({room,slide,answer})});
            }catch(e){setStatus('Abstimmung nicht möglich: '+e.message,'error');return}
          }
          voted[key]=answer;render();
        },
        onOriginal:()=>{originalView=true;render()}
      });
    }
    counter.textContent=slide<=123?String(slide).padStart(3,'0')+' / 123':'BONUS '+(slide-123)+' / 13';
    prev.disabled=slide===1;
    next.disabled=slide>=136||slide===123;
    document.title='Folie '+slide+' · '+s.title+' · Regelworkshop';
  };
  const jump=(num,userInitiated=false)=>{
    const n=Math.max(1,Math.min(136,Number(num)||1));
    if(userInitiated&&room){following=false;followButton.setAttribute('aria-pressed','false');followButton.textContent='▷ Live fortsetzen'}
    if(n!==slide||!stage.querySelector('.rw-card,.participant-original-view')){
      slide=n;originalView=false;render();
    }
  };
  prev.onclick=()=>jump(slide-1,true);
  next.onclick=()=>{if(slide!==123)jump(slide+1,true)};
  followButton.onclick=()=>{
    if(!room)return;
    following=!following;followButton.setAttribute('aria-pressed',String(following));
    followButton.textContent=following?'● Live folgen':'▷ Live fortsetzen';
    if(following)jump(remoteSlide,false);
  };
  const connectRoom=async(code)=>{
    const clean=String(code||'').trim().toUpperCase();
    if(!/^[A-HJ-NP-Z2-9]{6}$/.test(clean)){setStatus('Bitte gültigen sechsstelligen Raumcode eingeben.','error');return}
    input.value=clean;
    try{
      setStatus('Verbinde ...','connecting');
      const res=await call('/live.php?op=status&room='+encodeURIComponent(clean));
      room=clean;following=true;currentStatus=res;remoteSlide=res.slide;
      followButton.disabled=false;followButton.textContent='● Live folgen';followButton.setAttribute('aria-pressed','true');
      setStatus(res.active?'Verbunden · Raum '+room:'Live-Raum beendet · weiter selbst navigieren',res.active?'online':'offline');
      jump(res.slide,false);
      const href=new URL(location.href);href.searchParams.set('room',room);history.replaceState(null,'',href);
    }catch(e){setStatus('Raum nicht erreichbar: '+e.message,'error')}
  };
  form.onsubmit=e=>{e.preventDefault();connectRoom(input.value)};
  const refresh=async()=>{
    if(!room||loading||document.hidden)return;
    loading=true;
    try{
      const res=await call('/live.php?op=status&room='+encodeURIComponent(room));
      currentStatus=res;remoteSlide=res.slide;
      if(res.active){
        setStatus('Live · Raum '+room+' · Folie '+res.slide,'online');
        if(following)jump(res.slide,false);
      }else{
        setStatus('Referent hat die Live-Übertragung beendet.','offline');
        following=false;followButton.disabled=true;followButton.textContent='Live beendet';
      }
    }catch(e){setStatus('Verbindung unterbrochen – erneuter Versuch ...','error')}
    finally{loading=false}
  };
  stage.addEventListener('touchstart',e=>{
    if(e.target.closest('video,button,details'))return;
    stage.dataset.touchX=String(e.changedTouches[0]?.screenX??'');
  },{passive:true});
  stage.addEventListener('touchend',e=>{
    if(!stage.dataset.touchX)return;
    const diff=e.changedTouches[0].screenX-Number(stage.dataset.touchX);
    delete stage.dataset.touchX;
    if(Math.abs(diff)>90){if(diff>0)prev.click();else next.click()}
  },{passive:true});
  try{
    const res=await call('/resource.php?path=slides.json');
    slides=res.slides||[];
    if(slides.length!==136)throw Error('Unvollständige Folienliste');
    jump(1,false);
    const code=new URLSearchParams(location.search).get('room');
    if(code)await connectRoom(code);
    else setStatus('Ohne Raum · Folien selbst durchblättern','offline');
  }catch(e){
    stage.textContent='Workshop-Folien konnten nicht geladen werden: '+e.message;
    setStatus('Fehler beim Laden','error');
  }
  setInterval(refresh,2300);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh()});
  window.__workshopParticipant={getCurrent:()=>slide,follow:()=>following,room:()=>room};
})();

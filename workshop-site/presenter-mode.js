/* © Tennis-Schiri · separate Vorführansicht für 136 Workshop-Folien */
'use strict';
(()=>{
 const shell=document.getElementById('shell');
 const btn=document.getElementById('full-btn');
 if(!shell||!btn)return;
 const fullscreenSupported=!!(shell.requestFullscreen||shell.webkitRequestFullscreen);
 let active=false;
 btn.textContent='⛶ Präsentieren';
 btn.title='Präsentationsmodus ohne Kopfzeile/Leiste (F)';
 btn.setAttribute('aria-label','Präsentation bildschirmfüllend starten');
 const exit=document.createElement('button');
 exit.id='presenter-exit';
 exit.className='presenter-exit';
 exit.type='button';
 exit.textContent='✕ Präsentation beenden · Esc';
 exit.title='Vollbild schließen (Esc oder F)';
 exit.hidden=true;
 shell.appendChild(exit);
 function fullscreenElement(){return document.fullscreenElement||document.webkitFullscreenElement}
 function enter(){
   if(active)return;
   active=true;
   shell.classList.add('presentation-view');
   document.documentElement.classList.add('presenter-active');
   document.body.classList.add('presenter-active');
   exit.hidden=false;
   btn.setAttribute('aria-label','Präsentationsmodus beenden');
   if(fullscreenSupported){
     try{
       const request=(shell.requestFullscreen||shell.webkitRequestFullscreen).bind(shell);
       const p=request({navigationUI:'hide'});
       if(p&&typeof p.catch==='function')p.catch(()=>{/* CSS-Fallback bleibt aktiv */});
     }catch(e){/* Fallback für iOS/ältere Safari-Versionen */}
   }
 }
 function leave(){
   if(!active)return;
   active=false;
   shell.classList.remove('presentation-view');
   document.documentElement.classList.remove('presenter-active');
   document.body.classList.remove('presenter-active');
   exit.hidden=true;
   btn.setAttribute('aria-label','Präsentation bildschirmfüllend starten');
   if(fullscreenElement()){
     const request=document.exitFullscreen||document.webkitExitFullscreen;
     if(request){try{const p=request.call(document);if(p&&p.catch)p.catch(()=>{})}catch(e){}}
   }
 }
 btn.onclick=()=>active?leave():enter();
 exit.addEventListener('click',leave);
 document.addEventListener('fullscreenchange',()=>{if(active&&!fullscreenElement())leave()});
 document.addEventListener('webkitfullscreenchange',()=>{if(active&&!fullscreenElement())leave()});
 // Den bisherigen F-Shortcut des Workshop-Players bewusst ersetzen.
 document.addEventListener('keydown',e=>{
   if(!e.altKey&&!e.ctrlKey&&!e.metaKey&&e.key.toLowerCase()==='f'&&
      !['INPUT','TEXTAREA'].includes(document.activeElement?.tagName)){
     e.preventDefault();e.stopImmediatePropagation();
     active?leave():enter();return;
   }
   // Bei Safari ohne native Fullscreen-API bleibt der immersive CSS-Modus nutzbar.
   if(e.key==='Escape'&&active&&!fullscreenElement()&&
      !document.getElementById('modal-layer')?.classList.contains('open')&&
      !document.getElementById('video-layer')?.classList.contains('open')&&
      !document.getElementById('blackout')?.classList.contains('open'))leave();
 },true);
 // Direkter Aufruf /presentation.php?present=1: Browser-Fullscreen braucht
 // immer einen echten Nutzerklick; daher bewusst kein blockierter Auto-Start.
 if(new URLSearchParams(location.search).get('present')==='1'){
   const overlay=document.createElement('div');overlay.className='presenter-launch';
   overlay.innerHTML='<section class="presenter-launch-card" role="dialog" aria-modal="true" aria-labelledby="presenter-launch-title"><h2 id="presenter-launch-title">WORKSHOP IM VOLLBILD.</h2><p>136 Folien ohne Website-Rahmen präsentieren. Steuere mit den Pfeiltasten, Leertaste oder deinem USB-Presenter. <strong>Esc</strong> beendet die Vorführung.</p><div class="presenter-launch-actions"><button class="primary" type="button" data-action="start">⛶ Präsentation starten</button><button type="button" data-action="normal">Normale Ansicht</button></div></section>';
   document.body.appendChild(overlay);
   overlay.querySelector('[data-action="start"]').addEventListener('click',()=>{overlay.remove();enter()});
   overlay.querySelector('[data-action="normal"]').addEventListener('click',()=>overlay.remove());
   overlay.querySelector('[data-action="start"]').focus();
 }
 window.__workshopPresenter={start:enter,stop:leave,isActive:()=>active};
})();

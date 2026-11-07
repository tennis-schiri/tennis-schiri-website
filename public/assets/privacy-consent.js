/* Tennis-Schiri: eigene Datenschutz-Auswahl ohne Tracking/CMP-Drittanbieter.
 * Nur optionale Google-Karten; notwendige Verwaltungssitzungen und ausdrücklich
 * ausgewählte lokale Checklisten-Funktionen bleiben unabhängig davon nutzbar.
 */
(function(){
  'use strict';
  const KEY='ts-privacy-choice-v1';
  const MAX_AGE=365*24*60*60*1000;
  const VERSION=1;
  const $=id=>document.getElementById(id);
  let choice=null;
  let pendingMap=null;
  let mode='settings';
  let lastFocus=null;

  function readChoice(){
    try{
      const raw=localStorage.getItem(KEY);
      if(!raw)return null;
      const parsed=JSON.parse(raw);
      if(!parsed||parsed.version!==VERSION||typeof parsed.maps!=='boolean'||typeof parsed.savedAt!=='number'||Date.now()-parsed.savedAt<0||Date.now()-parsed.savedAt>MAX_AGE)return null;
      return {version:VERSION,maps:parsed.maps,savedAt:parsed.savedAt};
    }catch(_){return null;}
  }
  choice=readChoice();
  function broadcast(){
    window.dispatchEvent(new CustomEvent('ts:privacy-change',{detail:{maps:!!choice?.maps}}));
  }
  function save(maps){
    choice={version:VERSION,maps:!!maps,savedAt:Date.now()};
    try{localStorage.setItem(KEY,JSON.stringify(choice));}catch(_){}
    const banner=$('ts-pc-banner');
    if(banner)banner.hidden=true;
    broadcast();
  }
  function resetDialog(){
    pendingMap=null;
    mode='settings';
  }
  function closeDialog(){
    const dialog=$('ts-pc-dialog');
    if(dialog?.open)dialog.close();
    resetDialog();
    if(lastFocus?.isConnected)lastFocus.focus();
  }
  function showSettings(){
    showDialog('settings',null);
  }
  function showDialog(nextMode,onMapLoad){
    const dialog=$('ts-pc-dialog');
    if(!dialog)return;
    lastFocus=document.activeElement;
    mode=nextMode;
    pendingMap=typeof onMapLoad==='function'?onMapLoad:null;
    const isMap=mode==='map';
    $('ts-pc-modal-title').textContent=isMap?'Google Maps anzeigen?':'Datenschutz-Einstellungen';
    $('ts-pc-modal-description').textContent=isMap
      ? 'Beim Laden der Karte wird eine Verbindung zu Google aufgebaut. Dabei können personenbezogene Daten und weitere Speichertechniken verarbeitet werden. Du kannst die Karte nur dieses Mal oder grundsätzlich erlauben.'
      : 'Hier entscheidest du, ob externe Karten von Google Maps geladen werden dürfen. Es werden keine Analyse- oder Werbedienste eingesetzt.';
    $('ts-pc-settings-view').hidden=isMap;
    $('ts-pc-settings-actions').hidden=isMap;
    $('ts-pc-map-actions').hidden=!isMap;
    $('ts-pc-maps-checkbox').checked=!!choice?.maps;
    if(!dialog.open)dialog.showModal();
  }
  function requestMap(onMapLoad){
    if(typeof onMapLoad!=='function')return;
    if(choice?.maps){onMapLoad();return;}
    showDialog('map',onMapLoad);
  }
  function createUI(){
    document.body.insertAdjacentHTML('beforeend',`
<section class="ts-pc-banner" id="ts-pc-banner" role="region" aria-labelledby="ts-pc-title" hidden>
  <div class="ts-pc-banner-inner">
    <div class="ts-pc-banner-copy">
      <div class="ts-pc-eyebrow">DATENSCHUTZ &amp; COOKIES</div>
      <h2 id="ts-pc-title">Deine Privatsphäre zählt.</h2>
      <p>Wir nutzen keine Werbe- oder Analyse-Cookies. Nur notwendige Funktionen sind aktiv. Externe Karten von Google Maps laden wir erst mit deiner Zustimmung. <a href="/datenschutz/">Mehr zum Datenschutz</a></p>
    </div>
    <div class="ts-pc-banner-buttons">
      <button type="button" class="ts-pc-action ts-pc-solid" id="ts-pc-reject">Nur notwendige</button>
      <button type="button" class="ts-pc-action ts-pc-solid" id="ts-pc-accept">Alle akzeptieren</button>
      <button type="button" class="ts-pc-action ts-pc-border" id="ts-pc-customize">Einstellungen</button>
    </div>
  </div>
</section>
<dialog class="ts-pc-dialog" id="ts-pc-dialog" aria-labelledby="ts-pc-modal-title" aria-describedby="ts-pc-modal-description">
  <div class="ts-pc-modal-top">
    <div class="ts-pc-eyebrow">TENNIS-SCHIRI · DATENSCHUTZ</div>
    <button type="button" class="ts-pc-close" id="ts-pc-close" aria-label="Fenster schließen">×</button>
  </div>
  <h2 id="ts-pc-modal-title">Datenschutz-Einstellungen</h2>
  <p id="ts-pc-modal-description"></p>
  <div id="ts-pc-settings-view">
    <div class="ts-pc-option">
      <div><strong>Technisch notwendige Funktionen</strong><small>Damit die Seite funktioniert und deine Auswahl gemerkt wird. Dazu gehören gegebenenfalls erforderliche Sitzungscookies im geschützten Bereich.</small></div>
      <span class="ts-pc-required">Immer aktiv</span>
    </div>
    <label class="ts-pc-option ts-pc-optional">
      <div><strong>Externe Karten (Google Maps)</strong><small>Karten werden auch bei Zustimmung erst geladen, wenn du auf „Karte anzeigen“ klickst. Dabei können Daten an Google übertragen werden.</small></div>
      <input type="checkbox" id="ts-pc-maps-checkbox">
    </label>
    <p class="ts-pc-small">Die Auswahl wird lokal in deinem Browser gespeichert. Du kannst sie jederzeit im Seitenfuß ändern. <a href="/datenschutz/">Datenschutzerklärung</a></p>
  </div>
  <div class="ts-pc-dialog-actions" id="ts-pc-settings-actions">
    <button type="button" class="ts-pc-action ts-pc-solid" id="ts-pc-dialog-reject">Nur notwendige</button>
    <button type="button" class="ts-pc-action ts-pc-border" id="ts-pc-dialog-save">Auswahl speichern</button>
    <button type="button" class="ts-pc-action ts-pc-solid" id="ts-pc-dialog-accept">Alle akzeptieren</button>
  </div>
  <div class="ts-pc-dialog-actions" id="ts-pc-map-actions" hidden>
    <button type="button" class="ts-pc-action ts-pc-border" id="ts-pc-map-cancel">Abbrechen</button>
    <button type="button" class="ts-pc-action ts-pc-solid" id="ts-pc-map-once">Nur dieses Mal</button>
    <button type="button" class="ts-pc-action ts-pc-solid" id="ts-pc-map-always">Immer erlauben</button>
  </div>
</dialog>`);
    const footer=document.querySelector('.footer-bottom');
    if(footer){
      const button=document.createElement('button');
      button.type='button';button.className='ts-pc-footer';button.textContent='Datenschutz-Einstellungen';
      button.setAttribute('aria-haspopup','dialog');
      button.addEventListener('click',showSettings);
      footer.append(button);
    }
    $('ts-pc-reject').addEventListener('click',()=>save(false));
    $('ts-pc-accept').addEventListener('click',()=>save(true));
    $('ts-pc-customize').addEventListener('click',showSettings);
    $('ts-pc-dialog-reject').addEventListener('click',()=>{save(false);closeDialog();});
    $('ts-pc-dialog-accept').addEventListener('click',()=>{save(true);closeDialog();});
    $('ts-pc-dialog-save').addEventListener('click',()=>{save($('ts-pc-maps-checkbox').checked);closeDialog();});
    $('ts-pc-close').addEventListener('click',closeDialog);
    $('ts-pc-map-cancel').addEventListener('click',closeDialog);
    $('ts-pc-map-once').addEventListener('click',()=>{
      const callback=pendingMap;
      // Eine einmalige Kartenfreigabe erteilt keine dauerhafte Zustimmung.
      if(!choice)save(false);
      closeDialog();
      callback?.();
    });
    $('ts-pc-map-always').addEventListener('click',()=>{
      const callback=pendingMap;
      save(true);closeDialog();callback?.();
    });
    const dialog=$('ts-pc-dialog');
    dialog.addEventListener('close',()=>{pendingMap=null;mode='settings';});
    dialog.addEventListener('click',event=>{
      if(event.target===dialog)closeDialog();
    });
    if(!choice)$('ts-pc-banner').hidden=false;
  }

  // Erst installieren, wenn die DOM-Elemente vorhanden sind.
  window.TSPrivacy=Object.freeze({
    mapsAllowed:()=>choice?.maps===true,
    showSettings,
    requestMap,
  });
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',createUI,{once:true});
  else createUI();
})();

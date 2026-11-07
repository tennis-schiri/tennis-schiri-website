import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import path from 'node:path';

const publicDir='public';
const source=fs.readFileSync(path.join(publicDir,'assets/privacy-consent.js'),'utf8');
const css=fs.readFileSync(path.join(publicDir,'assets/site.css'),'utf8');
const maps=fs.readFileSync(path.join(publicDir,'assets/tournament-detail.js'),'utf8');
new Function(source);
new Function(maps);

const htmls=[
 'index.html','aktuelles/index.html','datenschutz/index.html','impressum/index.html',
 'kontakt/index.html','leistungen/index.html','lk-turnier/index.html','newsletter/index.html',
 'partner/index.html','referenzen/index.html','regelwissen/index.html','statistiken/index.html',
 'turniere/index.html','ueber-mich/index.html','turniere/termin.html'
];
for(const name of htmls){
 const data=fs.readFileSync(path.join(publicDir,name),'utf8');
 const prefix=name==='index.html'?'assets/':'../assets/';
 assert.ok(data.includes('src="'+prefix+'privacy-consent.js?v=1"'),name+' has no privacy script');
 assert.match(data, /site\.css\?v=20261107-(?:12|13)"/, name+' uses stale privacy styling');
 const p=data.indexOf('privacy-consent.js?v=1"');
 const b=data.indexOf('back-to-top.js?v=2"');
 assert.ok(p>=0&&b>p,'Consent must initialize before page scripts: '+name);
}
assert.ok(css.includes('.ts-pc-banner')&&css.includes('.ts-pc-dialog')&&css.includes('.ts-pc-solid'));
assert.ok(maps.includes('window.TSPrivacy.requestMap(loadMap)'), 'Map must not load without consent');
assert.ok(maps.includes("event.detail?.maps===false"), 'Revocation must remove active maps');
assert.ok(maps.includes('area.replaceChildren(iframe)'));
const privacy=fs.readFileSync(path.join(publicDir,'datenschutz/index.html'),'utf8');
assert.ok(privacy.includes('ts-privacy-choice-v1'));
assert.ok(privacy.includes('Google Maps'));
assert.ok(privacy.includes('Datenschutz-Einstellungen'));

function init(existing){
 const storage=new Map(existing?[[ 'ts-privacy-choice-v1',JSON.stringify(existing) ]]:[]);
 const elements=new Map();
 const events=[];
 function elem(){
  return {
   hidden:true, checked:false, open:false, isConnected:true, listeners:{}, children:[],
   addEventListener(type,fn){(this.listeners[type]??=[]).push(fn);},
   click(){for(const fn of this.listeners.click||[])fn({target:this});},
   append(node){this.children.push(node);},
   setAttribute(){},
   focus(){},
   showModal(){this.open=true;},
   close(){this.open=false;for(const fn of this.listeners.close||[])fn({target:this});}
  };
 }
 const footer=elem();
 const document={
  readyState:'complete',activeElement:elem(),
  getElementById(id){return elements.get(id)||null;},
  querySelector(q){return q==='.footer-bottom'?footer:null;},
  createElement(){return elem();},
  body:{insertAdjacentHTML(_position,html){
   for(const item of html.matchAll(/\bid="([^"]+)"/g)){
    if(!elements.has(item[1]))elements.set(item[1],elem());
   }
  }}
 };
 const window={dispatchEvent(e){events.push(e);}};
 const localStorage={
  getItem(k){return storage.get(k)||null;},
  setItem(k,v){storage.set(k,v);},
  removeItem(k){storage.delete(k);}
 };
 function CustomEvent(type,opts){this.type=type;this.detail=opts.detail;}
 vm.runInNewContext(source,{document,window,localStorage,CustomEvent,Date,console},{filename:'privacy-consent.js',timeout:1500});
 return {window,elements,events,storage,footer,click(id){const e=elements.get(id);assert.ok(e,'Missing element '+id);e.click();},get(id){return elements.get(id);}};
}
const first=init();
assert.equal(first.get('ts-pc-banner').hidden,false,'New users must see banner');
assert.equal(first.window.TSPrivacy.mapsAllowed(),false);
first.click('ts-pc-reject');
assert.equal(first.get('ts-pc-banner').hidden,true);
assert.equal(JSON.parse(first.storage.get('ts-privacy-choice-v1')).maps,false);
let loads=0;
first.window.TSPrivacy.requestMap(()=>loads++);
assert.equal(loads,0,'Map must await consent');
assert.equal(first.get('ts-pc-dialog').open,true);
first.click('ts-pc-map-once');
assert.equal(loads,1,'Single-use consent must load requested map');
assert.equal(first.window.TSPrivacy.mapsAllowed(),false,'One-time consent must not persist');
first.window.TSPrivacy.requestMap(()=>loads++);
first.click('ts-pc-map-always');
assert.equal(loads,2);
assert.equal(first.window.TSPrivacy.mapsAllowed(),true);
first.window.TSPrivacy.showSettings();
first.get('ts-pc-maps-checkbox').checked=false;
first.click('ts-pc-dialog-save');
assert.equal(first.window.TSPrivacy.mapsAllowed(),false,'Revocation must persist');
assert.ok(first.events.some(e=>e.type==='ts:privacy-change'&&e.detail.maps===false));
assert.ok(first.footer.children[0],'Footer must offer settings link');
first.footer.children[0].click();
assert.equal(first.get('ts-pc-dialog').open,true);
first.click('ts-pc-close');
const second=init();
second.click('ts-pc-accept');
assert.equal(second.window.TSPrivacy.mapsAllowed(),true);
const persisted=JSON.parse(second.storage.get('ts-privacy-choice-v1'));
const third=init(persisted);
assert.equal(third.get('ts-pc-banner').hidden,true,'Saved preferences must suppress repeat banner');
assert.equal(third.window.TSPrivacy.mapsAllowed(),true);
console.log('Privacy consent: 15 pages, opt-in, rejection, one-time maps, revocation and persistence tested.');

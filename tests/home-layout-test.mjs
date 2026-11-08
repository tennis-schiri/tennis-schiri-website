// Tennis-Schiri: visual hierarchy, functions and workshop deep links.
import fs from 'node:fs';
import assert from 'node:assert/strict';
import vm from 'node:vm';

const html=fs.readFileSync('public/index.html','utf8');
const css=fs.readFileSync('public/assets/home-refresh.css','utf8');
const script=fs.readFileSync('public/assets/home-refresh.js','utf8');
assert.ok(html.includes('<body class="home-v2">'));
assert.ok(html.includes('href="assets/home-refresh.css?v=2"'));
assert.ok(html.includes('src="assets/home-refresh.js?v=1"'));
const sections=['id="start"','class="tennis-quote-strip"','class="quicknav"','id="aktuelles"','id="turniere"','id="spielerhilfen"','id="regelwissen"','id="leistungen"','id="instagram"','id="tennilo"','id="newsletter"','class="community-spotlight"','id="ueber-mich"','id="kontakt"'];
let last=-1;
for(const token of sections){
 const position=html.indexOf(token);
 assert.ok(position>last,'Homepage sections out of order or missing: '+token);
 last=position;
}
for(const target of [
 'id="event-list"', 'class="featured-tournament"', 'id="home-tourney-summary"',
 'data-filter="alle"', 'data-filter="lk"', 'id="hero-stat-value"',
 'id="hero-stat-next"','id="tennis-quote-next"','class="rules-home-answer"',
 'href="regelwissen/#linienball"', 'href="regelwissen/#regelwerke"',
 'href="lk-turnier/"','href="mein-turniertag/"',
 'data-instagram-account="tennis-schiri"', 'data-instagram-account="tennilo"',
 'src="assets/tennilo-logo.png"', 'src="assets/tennilo-icon.png"',
 'class="newsletter-form"', 'class="workshop-form"', 'action="workshop.php"',
 'id="regelworkshop-anmeldung"', 'id="workshop-signup-home-title"',
 'class="community-join"', 'class="home-news-card home-news-featured"'
])assert.ok(html.includes(target),'Homepage feature missing: '+target);
const eventCount=(html.match(/class="event tournament-row/g)||[]).length;
assert.ok(eventCount>=10,'Tournament data missing: '+eventCount);
assert.ok(html.includes('<details class="home-workshop-details" id="regelworkshop-anmeldung">'));
assert.ok(!html.includes('class="workshop-feature"'),'Duplicate full-size workshop promotion remained');

assert.ok(html.includes('href="assets/home-refresh.css?v=2"'),'Homepage CSS cache must refresh');
// Navigation from top to bottom: opening quote, news, events, services, links, contact.
const between=(start,end)=>{
 const a=html.indexOf(start),b=html.indexOf(end,a+start.length);
 assert.ok(a>=0&&b>a,'Missing homepage zone: '+start);
 return html.slice(a,b);
};
const topNews=between('<div class="home-zone home-zone--news">','<div class="home-zone home-zone--calendar">');
assert.ok(topNews.includes('id="aktuelles"')&&!topNews.includes('class="community-spotlight"'));
const connect=between('<div class="home-zone home-zone--closing">','<div class="home-zone home-zone--personal">');
assert.ok(connect.includes('id="newsletter"')&&connect.includes('class="community-spotlight"'),'Newsletter and WhatsApp must share a zone');
const bottom=between('<div class="home-zone home-zone--personal">','</main>');
assert.ok(bottom.includes('id="ueber-mich"')&&bottom.includes('id="kontakt"'),'Personal section must lead directly to the contact area');
assert.ok(bottom.indexOf('id="ueber-mich"')<bottom.indexOf('id="kontakt"'));
assert.ok(!bottom.includes('id="newsletter"')&&!bottom.includes('id="tennilo"'),'No intervening features between about and contact');
assert.ok(html.includes('</strong> <span>Turniere und Tennis-Community</span>'),'Hero copy needs visible word spacing');
assert.ok(html.includes('</strong> <span>Erfahrung als lizenzierter Schiedsrichter</span>'),'Second hero line needs visible word spacing');

for(const className of ['home-zone--news','home-zone--calendar','home-zone--knowledge','home-zone--services','home-zone--activity','home-zone--profile','home-zone--closing','home-zone--personal','home-guide-grid'])
 assert.ok(html.includes(className),'Missing content group: '+className);
for(const fragment of [
 '.home-v2 .home-zone{border-top:', '.home-v2 .section{padding:var(--home-gap)',
 '.home-v2 .hero-inner{', '.home-v2 #turniere .event.tournament-row .tournament-details{display:none}',
 '.home-v2 .home-guide-card', '.home-v2 .home-workshop-details',
 '@media(max-width:760px)', '@media(max-width:620px)'
])assert.ok(css.includes(fragment),'Missing responsive design rule '+fragment);

// Follow an in-page workshop CTA and ensure the compact form is revealed.
const workshop={open:false, scrollIntoView(){this.scrolled=true;}};
const callbacks={};
const context={
  document:{getElementById:()=>workshop,addEventListener:(name,cb)=>callbacks[name]=cb},
  location:{hash:'#regelworkshop-anmeldung'},
  window:{addEventListener:(name,cb)=>callbacks[name]=cb},
  requestAnimationFrame:cb=>cb(),
};
vm.runInNewContext(script,context,{filename:'home-refresh.js',timeout:1000});
assert.equal(workshop.open,true,'Opening the registration deep link must expand the details');
assert.equal(workshop.scrolled,true,'Registration deep link must scroll to the form');
workshop.open=false;
context.location.hash='#other';
callbacks.hashchange();
assert.equal(workshop.open,false,'Unrelated hash should not open registration');
context.location.hash='#workshop-signup-home-title';
callbacks.hashchange();
assert.equal(workshop.open,true,'Form heading deep link must work');
console.log('Homepage layout: adjusted hierarchy, 8 zones, '+eventCount+' tournaments, interactive sections and workshop deep links OK.');

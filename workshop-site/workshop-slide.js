/* Tennis-Schiri Regelworkshop · responsive, original-aware slide renderer */
'use strict';
(function(){
  const make=(tag,cls,value)=>{const el=document.createElement(tag);if(cls)el.className=cls;if(value!==undefined)el.textContent=String(value);return el};
  const compact=t=>String(t||'').replace(/ORIGINALMATERIAL\s*\/\s*ORIGINALTEXT:\s*/g,'').replace(/^ORIGINALFOLIE \d+[^\n]*\n/gm,'').replace(/\n---\n/g,'\n\n').replace(/\n{3,}/g,'\n\n').trim();
  const options=s=>{const o={};const t=s.text||[];for(let i=0;i<t.length-1;i++)if(['A','B','C'].includes(t[i]))o[t[i]]=t[i+1];return o};
  const svgNet=[
    '<svg viewBox="0 0 980 360" role="img" aria-label="Tennisnetz mit deutlich abgesenkter Netzmitte">',
    '<defs><pattern id="netmesh" width="25" height="23" patternUnits="userSpaceOnUse"><path d="M25 0H0V23" fill="none" stroke="#a9c6aa" opacity=".55"/></pattern></defs>',
    '<path d="M78 74 Q490 136 902 74 V284 H78Z" fill="url(#netmesh)" stroke="#9dbea1" stroke-width="2"/>',
    '<path d="M78 74 Q490 136 902 74" fill="none" stroke="#f7fff1" stroke-width="8" stroke-linecap="round"/>',
    '<path d="M490 105 V284" stroke="#d2fc46" stroke-width="10"/>',
    '<path d="M78 52 V291 M902 52 V291" stroke="#f7fff1" stroke-width="13" stroke-linecap="round"/>',
    '<path d="M274 91 V290 M706 91 V290" stroke="#d2fc46" stroke-width="4"/>',
    '<g fill="#f6ffeb" font-weight="850" font-family="system-ui,sans-serif" font-size="26">',
    '<text x="78" y="31" text-anchor="middle">1,07 m</text><text x="902" y="31" text-anchor="middle">1,07 m</text>',
    '<text x="490" y="83" text-anchor="middle" fill="#d2fc46">91,4 cm</text>',
    '<text x="274" y="330" text-anchor="middle" font-size="20">EINZELSTÜTZE</text><text x="274" y="353" text-anchor="middle" font-size="13">91,4 cm außerhalb</text>',
    '<text x="490" y="330" text-anchor="middle" font-size="20">MITTELBAND</text>',
    '<text x="706" y="330" text-anchor="middle" font-size="20">EINZELSTÜTZE</text><text x="706" y="353" text-anchor="middle" font-size="13">91,4 cm außerhalb</text></g></svg>'
  ].join('');
  const svgCourt=[
    '<svg viewBox="0 0 940 440" role="img" aria-label="Tennisplatz mit Einzel- und Doppellinien">',
    '<rect x="90" y="65" width="760" height="310" rx="7" fill="#bf8051" stroke="#faf7ec" stroke-width="5"/>',
    '<g fill="none" stroke="#fbf7ed" stroke-width="4">',
    '<line x1="90" y1="107" x2="850" y2="107"/><line x1="90" y1="333" x2="850" y2="333"/>',
    '<line x1="305" y1="107" x2="305" y2="333"/><line x1="635" y1="107" x2="635" y2="333"/>',
    '<line x1="305" y1="220" x2="635" y2="220"/><line x1="470" y1="65" x2="470" y2="375" stroke="#d2fc46" stroke-width="6"/></g>',
    '<g fill="#f9fff1" font-family="system-ui,sans-serif" font-weight="800" font-size="22">',
    '<text x="470" y="39" text-anchor="middle">23,77 m · LÄNGE</text>',
    '<text x="24" y="225" transform="rotate(-90 24 225)" text-anchor="middle">10,97 m · DOPPEL</text>',
    '<text x="909" y="225" transform="rotate(90 909 225)" text-anchor="middle">8,23 m · EINZEL</text></g></svg>'
  ].join('');
  function details(parent,text){
    if(!text)return;
    const d=make('details','rw-source-details');d.append(make('summary','', 'Mehr aus dem Original'));
    d.append(make('div','rw-source-text',text));parent.append(d);
  }
  function render(host,s,opts={}){
    if(!host||!s)return;
    host.replaceChildren();
    const card=make('article','rw-card rw-type-'+s.type);
    const header=make('header','rw-top');
    header.append(make('span','rw-kicker',s.type==='question'?'DEINE ENTSCHEIDUNG':s.type==='answer'?'DIE AUFLÖSUNG':s.type==='video'?'VIDEOANALYSE':s.chapter));
    header.append(make('span','rw-number',s.no<=123?'FOLIE '+String(s.no).padStart(3,'0'):'BONUS '+String(s.no-123).padStart(2,'0')));
    card.append(header);
    const area=make('div','rw-main');
    const content=make('div','rw-copy');
    content.append(make('h2','rw-title',s.title));
    const raw=Array.isArray(s.text)?s.text.filter(Boolean):[];
    if(s.type==='question'){
      content.append(make('p','rw-standfirst','Was würdest du entscheiden?'));
      const grid=make('div','rw-choices'),choices=options(s);
      for(const choice of ['A','B','C']){
        if(!choices[choice])continue;
        const btn=make('button','rw-choice');
        btn.type='button';btn.append(make('b','rw-choice-letter',choice),make('span','',choices[choice]));
        if(opts.voted===choice)btn.classList.add('is-voted');
        if(opts.onVote){btn.disabled=!!opts.voted;btn.addEventListener('click',()=>opts.onVote(choice))}
        else btn.addEventListener('click',()=>btn.classList.toggle('is-voted'));
        grid.append(btn);
      }
      content.append(grid);
      content.append(make('p','rw-hint',opts.voted?'Stimme gezählt. Auflösung auf der nächsten Folie.':'Erst entscheiden, dann gemeinsam auflösen.'));
      if(opts.isHost&&opts.votes)content.append(make('p','rw-tally','Live-Abstimmung: '+Object.values(opts.votes).reduce((a,b)=>a+Number(b),0)+' Stimmen · A '+opts.votes.A+' / B '+opts.votes.B+' / C '+opts.votes.C));
    }else if(s.type==='answer'){
      const candidates=raw.filter(x=>!['AUFLÖSUNG','A','B','C'].includes(x)&&x!==s.title&&!x.includes('PUBLIKUM ENTSCHEIDET'));
      content.append(make('p','rw-answer',candidates.at(-1)||'Entscheidung gemeinsam besprechen.'));
      details(content,compact(s.notes));
    }else if(s.type==='video'){
      content.append(make('p','rw-standfirst','Anschauen · Anhalten · Entscheiden'));
      const media=make('video','rw-video');media.controls=true;media.playsInline=true;media.preload='metadata';media.src=s.video;
      media.setAttribute('aria-label',s.title);media.addEventListener('error',()=>{const t=make('p','rw-video-error','Video nicht verfügbar – Verbindung oder Medienzugang prüfen.');content.append(t)},{once:true});
      content.append(media);content.append(make('p','rw-hint','Mit ▶ starten, für die Diskussion pausieren. Originalvideos können auf Mobilgeräten anders skaliert erscheinen.'));
    }else{
      const seen=new Set([s.title.toLowerCase(),'REGELFRAGE'.toLowerCase()]);
      const lines=raw.filter(x=>!seen.has(x.toLowerCase())&&!x.startsWith('SPRECHER-IMPULS'));
      const max=s.no===1?3:10;
      const list=make('div','rw-points');
      lines.slice(0,max).forEach(x=>list.append(make('p','rw-point',x)));
      content.append(list);
      if(s.no===14||s.no===15){
        const graphic=make('div','rw-graphic');graphic.innerHTML=s.no===15?svgNet:svgCourt;content.append(graphic);
        if(s.no===15)content.append(make('p','rw-hint','Zwei verschiedene Maße: 91,4 cm Netzhöhe in der Mitte. Einzelstützen stehen jeweils 91,4 cm außerhalb der Einzel-Seitenlinie und halten die Netzkante dort auf 1,07 m.'));
      }
      if(s.no!==1)details(content,compact(s.notes));
    }
    area.append(content);
    // Historiemotive: als Comic-Interpretation kenntlich, Originalfolie bleibt
    // über den unveränderten Original-Button im Footer erreichbar.
    const historicalArt={
      5:{file:'hc02',alt:'Illustrierte Szene des Jeu de Paume in einer französischen Halle des 17. Jahrhunderts'},
      7:{file:'hc02',alt:'Comic-Interpretation eines historischen Jeu-de-Paume-Spiels'},
      10:{file:'hc02',alt:'Historisch inspirierte Hallentennis-Szene mit zeittypischer Kleidung und hölzernen Schlägern'},
      11:{file:'hc01',alt:'Comic-Interpretation von Lawn Tennis im ausgehenden 19. Jahrhundert'},
      12:{file:'hc01',alt:'Historisches Lawn Tennis mit zeittypischen Schlägern, Spielern und Zuschauern'}
    };
    if(historicalArt[s.no]){
      const chosen=historicalArt[s.no];
      const figure=make('figure','rw-history-art');
      const img=make('img','rw-history-image');
      img.src='/resource.php?path=comics/'+chosen.file+'.webp';
      img.alt=chosen.alt+'; moderne künstlerische Neugestaltung, kein historisches Original';
      img.loading='lazy';img.decoding='async';
      figure.append(img,make('figcaption','rw-history-caption','COMIC-INTERPRETATION · HISTORISCH INSPIRIERT'));
      area.append(figure);card.classList.add('rw-history-slide');
      if([5,11].includes(s.no))card.classList.add('rw-history-feature');
    }
    if([1,3,4,120,123].includes(s.no)){
      const aside=make('aside','rw-portrait');
      const photo=make('img');photo.src=s.no===4?'assets/workshop-michael-portrait.jpg':'assets/workshop-michael-tennis.jpg';photo.alt='Original-Tennisfoto mit Schläger auf dem Sandplatz';photo.loading='lazy';aside.append(photo);area.append(aside);card.classList.add('rw-with-photo');
    }
    if(s.type==='video')card.classList.add('rw-video-card');
    card.append(area);
    const foot=make('footer','rw-foot');
    foot.append(make('span','',Array.isArray(s.source)?'Originalfolie'+(s.source.length>1?'n ':' ')+s.source.join(', '):'Originalmaterial'));
    if(opts.onOriginal){
      const btn=make('button','rw-original','Originalfolie ansehen ↗');btn.type='button';btn.addEventListener('click',opts.onOriginal);foot.append(btn);
    }else foot.append(make('span','','TENNIS-SCHIRI · REGELWORKSHOP'));
    card.append(foot);host.append(card);
  }
  window.TSWorkshopSlide={render,options,compact};
})();

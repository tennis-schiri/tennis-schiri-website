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
  function readInPages(text,label){
    // Volle Originaltexte bleiben vorhanden, aber nie als Scrollfeld IN der Folie.
    // Die Zusatzinformation öffnet sich in einem separaten, paginierten Fenster.
    const normalized=String(text).trim();
    const pages=[];
    const words=normalized.split(/\s+/).filter(Boolean);
    let page='';
    for(const word of words){
      if(page.length+word.length+1>440&&page){pages.push(page);page='';}
      page+=(page?' ':'')+word;
    }
    if(page)pages.push(page);
    let i=0;
    const overlay=make('div','rw-notes-overlay');
    overlay.setAttribute('role','dialog');overlay.setAttribute('aria-modal','true');
    overlay.setAttribute('aria-label',label||'Zusätzliche Originalinformationen');
    const panel=make('section','rw-notes-panel');
    const head=make('div','rw-notes-head');
    const heading=make('h2','',label||'Originalinformationen');
    const close=make('button','rw-notes-close','✕ Schließen');
    close.type='button';
    head.append(heading,close);
    const body=make('div','rw-notes-body');
    const nav=make('div','rw-notes-nav');
    const prev=make('button','','← Zurück'),counter=make('span','',''),next=make('button','','Weiter →');
    prev.type=next.type='button';nav.append(prev,counter,next);
    function paint(){body.textContent=pages[i]||'';counter.textContent=(i+1)+' / '+pages.length;prev.disabled=i===0;next.disabled=i===pages.length-1;}
    const onKey=e=>{if(e.key==='Escape'){e.preventDefault();dismiss()}};
    function dismiss(){document.removeEventListener('keydown',onKey,true);overlay.remove();}
    close.addEventListener('click',dismiss);
    overlay.addEventListener('click',e=>{if(e.target===overlay)dismiss()});
    prev.addEventListener('click',()=>{if(i>0){i--;paint()}});
    next.addEventListener('click',()=>{if(i<pages.length-1){i++;paint()}});
    panel.append(head,body,nav);overlay.append(panel);
    document.body.append(overlay);document.addEventListener('keydown',onKey,true);
    paint();close.focus();
  }
  function details(parent,text,label='Mehr aus dem Original'){
    if(!text)return;
    const button=make('button','rw-source-open',label+' ↗');
    button.type='button';button.addEventListener('click',()=>readInPages(text,label));
    parent.append(button);
  }
  // Jede bildbasierte Folie bekommt ein eigenes Motiv; keine Doppelbelegung.
  // Technische Zeichnungen und Regelfragen bleiben bewusst auf ruhigem Hintergrund.
  const imagery={
    3: {src:'assets/workshop-michael-tennis.jpg',alt:'Tennis-Aktionsfoto von Michael Wiedeck',focal:'50% 0%',kind:'photo'},
    4: {src:'assets/workshop-michael-portrait.jpg',alt:'Michael Wiedeck auf dem Tennisplatz',focal:'50% 0%',kind:'photo'},
    5: {src:'resource.php?path=comics/hc02.webp',alt:'Comic-Interpretation von Jeu de Paume im 17. Jahrhundert',focal:'62% 48%',kind:'illustration'},
    6: {src:'resource.php?path=comics/jeu-de-paume-original-comic.webp',alt:'Historische Darstellung des Jeu de Paume',focal:'70% 44%',kind:'illustration'},
    7: {src:'resource.php?path=comics/wingfield-original-comic.webp',alt:'Historische Darstellung von Walter Clopton Wingfield mit Tennisschläger',focal:'67% 32%',kind:'illustration'},
    10:{src:'resource.php?path=comics/wimbledon-1877-original-comic.webp',alt:'Historische Illustration eines frühen Wimbledon-Turniers',focal:'65% 53%',kind:'illustration'},
    11:{src:'resource.php?path=comics/hc01.webp',alt:'Comic-Interpretation von Lawn Tennis im 19. Jahrhundert',focal:'62% 50%',kind:'illustration'},
    12:{src:'resource.php?path=comics/lawn-tennis-1887-original-comic.webp',alt:'Historische farbige Illustration Lawn Tennis 1887',focal:'65% 55%',kind:'illustration'},
    120:{src:'assets/workshop-michael-kneeling.jpg',alt:'Tennisporträt auf dem Sandplatz',focal:'50% 0%',kind:'photo'}
  };
  const uniqueImages=Object.values(imagery).map(item=>item.src);
  if(new Set(uniqueImages).size!==uniqueImages.length)throw Error('Workshop: Bildmotive dürfen sich nicht wiederholen');
  // Individuelle Begrüßungsfolie für jeden Verein / Workshoptermin.
  // Ausschließlich textContent: Nutzereingaben können kein HTML einschleusen.
  function renderWelcome(host,welcome={}){
    const card=make('article','rw-card rw-welcome');
    const bg=make('div','rw-welcome-court');
    bg.setAttribute('aria-hidden','true');
    card.append(bg);
    const header=make('header','rw-welcome-head');
    header.append(make('span','rw-welcome-eyebrow','TENNIS-SCHIRI · REGELWORKSHOP'));
    header.append(make('span','rw-welcome-number','01 / 123'));
    card.append(header);
    const main=make('div','rw-welcome-main');
    const above=make('p','rw-welcome-overline','Schön, dass ihr da seid!');main.append(above);
    main.append(make('h1','rw-welcome-title','Herzlich willkommen'));
    main.append(make('p','rw-welcome-subtitle','zum Regelworkshop'));
    const detail=make('div','rw-welcome-details');
    const venue=make('section','rw-welcome-venue');
    venue.append(make('span','rw-welcome-label','VEREIN / ORT'));
    const parts=[welcome.verein,welcome.ort].filter(x=>typeof x==='string'&&x.trim());
    venue.append(make('strong','rw-welcome-venue-text'+(!parts.length?' rw-empty':''),parts.join(' · ')||'Verein / Ort'));
    detail.append(venue);
    const row=make('div','rw-welcome-date-row');
    const date=make('section','rw-welcome-field');
    date.append(make('span','rw-welcome-label','DATUM'));
    let dateText='Datum';
    if(typeof welcome.datum==='string'&&/^\d{4}-\d{2}-\d{2}$/.test(welcome.datum)){
      const [year,month,day]=welcome.datum.split('-');
      dateText=day+'.'+month+'.'+year;
    }
    date.append(make('strong',!welcome.datum?'rw-empty':'',dateText));row.append(date);
    const time=make('section','rw-welcome-field');
    time.append(make('span','rw-welcome-label','UHRZEIT'));
    time.append(make('strong',!welcome.uhrzeit?'rw-empty':'',(welcome.uhrzeit||'Uhrzeit')+(welcome.uhrzeit?' Uhr':'')));
    row.append(time);detail.append(row);main.append(detail);
    card.append(main);
    const foot=make('footer','rw-welcome-foot');
    foot.append(make('span','','WISSEN · ENTSCHEIDEN · TENNIS'));
    foot.append(make('span','','TENNIS-SCHIRI'));
    card.append(foot);
    host.append(card);
  }

  function render(host,s,opts={}){
    if(!host||!s)return;
    host.replaceChildren();
    if(s.no===1){renderWelcome(host,opts.welcome||{});return;}
    const card=make('article','rw-card rw-type-'+s.type);
    const isHistory=String(s.chapter).includes('GESCHICHTE');
    if(isHistory)card.classList.add('rw-history-slide','rw-vintage');
    const imageData=imagery[s.no];
    if(imageData){
      card.classList.add('rw-immersive','rw-image-'+imageData.kind);
      if(s.no===6||s.no===7)card.classList.add('rw-art-portrait');
      if(imageData.kind==='photo')card.classList.add('rw-person-photo');
      const background=make('figure','rw-immersive-media');
      const img=make('img','rw-immersive-image');img.src=imageData.src;
      img.alt=imageData.alt;img.loading='lazy';img.decoding='async';
      img.style.objectPosition=imageData.focal;
      background.append(img);
      const credit=imageData.kind==='illustration'?'COMIC-INTERPRETATION · KEIN ORIGINALFOTO':
                   imageData.kind==='original'?'HISTORISCHE ORIGINALVORLAGE':'TENNISFOTO';
      background.append(make('figcaption','rw-immersive-credit',credit));
      card.append(background);
    }
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
      const max=s.no===1?2:4;
      const list=make('div','rw-points');
      lines.slice(0,max).forEach(x=>list.append(make('p','rw-point',x)));
      content.append(list);
      if(lines.length>max)details(content,lines.slice(max).join('  ·  '),'Weitere Folieninhalte');
      if(s.no===14||s.no===15){
        const graphic=make('div','rw-graphic');graphic.innerHTML=s.no===15?svgNet:svgCourt;content.append(graphic);
        if(s.no===15)content.append(make('p','rw-hint','Zwei verschiedene Maße: 91,4 cm Netzhöhe in der Mitte. Einzelstützen stehen jeweils 91,4 cm außerhalb der Einzel-Seitenlinie und halten die Netzkante dort auf 1,07 m.'));
      }
      if(s.no!==1)details(content,compact(s.notes));
    }
    area.append(content);
    if(s.type==='video')card.classList.add('rw-video-card');
    card.append(area);
    const foot=make('footer','rw-foot');
    foot.append(make('span','',Array.isArray(s.source)?'Originalfolie'+(s.source.length>1?'n ':' ')+s.source.join(', '):'Originalmaterial'));
    if(opts.onOriginal){
      const btn=make('button','rw-original','Originalfolie ansehen ↗');btn.type='button';btn.addEventListener('click',opts.onOriginal);foot.append(btn);
    }else foot.append(make('span','','TENNIS-SCHIRI · REGELWORKSHOP'));
    card.append(foot);host.append(card);
    // Verkleinert überschießende Inhalte vor der Anzeige; KEIN Scrollen,
    // KEIN Abschneiden langer Regelfalltexte ohne Größenanpassung.
    if(typeof window!=='undefined'&&typeof window.requestAnimationFrame==='function'){
      window.requestAnimationFrame(()=>{
        if(!card.isConnected||s.type==='video')return;
        const copy=card.querySelector('.rw-copy');
        if(!copy||copy.clientHeight<30)return;
        const scalable=copy.querySelectorAll('.rw-title,.rw-point,.rw-choice,.rw-answer,.rw-hint,.rw-standfirst,.rw-source-open');
        for(let attempt=0;attempt<7;attempt++){
          if(copy.scrollHeight<=copy.clientHeight+2)break;
          scalable.forEach(node=>{
            const current=parseFloat(window.getComputedStyle(node).fontSize);
            if(Number.isFinite(current)&&current>10.5)node.style.setProperty('font-size',Math.max(10.5,current*.91)+'px','important');
          });
        }
      });
    }
  }
  window.TSWorkshopSlide={render,options,compact};
})();

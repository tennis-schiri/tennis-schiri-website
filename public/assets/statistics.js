/* Nur freigegebene, anonymisierte Sportkennzahlen. Die interne Tabelle wird
   weder veröffentlicht noch aus dem Browser abgefragt. */
(() => {
  'use strict';
  const scriptUrl = document.currentScript?.src;
  if (!scriptUrl) return;
  const dataUrl = new URL('../data/statistics.json', scriptUrl);
  const number = value => new Intl.NumberFormat('de-DE').format(value);

  function initHero(data) {
    const box = document.getElementById('hero-statistic');
    const next = document.getElementById('hero-stat-next');
    if (!box || !next || !Array.isArray(data.facts) || !data.facts.length) return;
    const value = document.getElementById('hero-stat-value');
    const label = document.getElementById('hero-stat-label');
    const detail = document.getElementById('hero-stat-detail');
    if (!value || !label || !detail) return;
    let index = -1;
    function show(random = false) {
      if (random && data.facts.length > 1) {
        const offset = 1 + Math.floor(Math.random() * (data.facts.length - 1));
        index = (Math.max(index, 0) + offset) % data.facts.length;
      } else {
        index = (index + 1) % data.facts.length;
      }
      const fact = data.facts[index];
      value.textContent = fact.value;
      label.textContent = fact.label;
      detail.textContent = fact.detail;
    }
    index = Math.floor(Math.random() * data.facts.length);
    const initial = data.facts[index];
    value.textContent = initial.value;
    label.textContent = initial.label;
    detail.textContent = initial.detail;
    next.addEventListener('click', () => show(true));
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reducedMotion) {
      window.setInterval(() => {
        if (!document.hidden) show(true);
      }, 14000);
    }
  }

  function createBar(value, max, label, sub, unit = '') {
    const item = document.createElement('div');
    item.className = 'stats-bar-item';
    const top = document.createElement('div');
    top.className = 'stats-bar-top';
    const name = document.createElement('strong');
    name.textContent = label;
    const reading = document.createElement('span');
    reading.textContent = number(value) + unit;
    top.append(name, reading);
    const track = document.createElement('div');
    track.className = 'stats-bar-track';
    const fill = document.createElement('div');
    fill.className = 'stats-bar-fill';
    fill.style.width = (max ? Math.max(0, Math.min(100, value / max * 100)) : 0) + '%';
    track.appendChild(fill);
    item.append(top, track);
    if (sub) {
      const note = document.createElement('small');
      note.textContent = sub;
      item.appendChild(note);
    }
    return item;
  }

  function renderChart(targetId, years, prop, suffix = '') {
    const target = document.getElementById(targetId);
    if (!target) return;
    target.replaceChildren();
    const max = Math.max(...years.map(y => y[prop] || 0));
    years.forEach(year => target.appendChild(createBar(year[prop], max, year.year + (year.partial ? ' *' : ''), null, suffix)));
  }

  const pctValue = (count,total) => total ? count / total * 100 : 0;
  const pct = (count,total) => pctValue(count,total).toLocaleString('de-DE',{minimumFractionDigits:1,maximumFractionDigits:1}) + ' %';
  const formatOne = n => Number(n).toLocaleString('de-DE',{minimumFractionDigits:1,maximumFractionDigits:1});
  const setText = (id, content) => {
    const el = document.getElementById(id);
    if (el) el.textContent = String(content);
  };
  function setRatioBar(id, count, total, label) {
    const bar = document.getElementById(id);
    if (bar) bar.style.width = Math.max(0,Math.min(100,pctValue(count,total))) + '%';
    const track = bar?.parentElement;
    if (track) track.setAttribute('aria-label',label || number(count)+' von '+number(total)+' ('+pct(count,total)+')');
  }
  function bindYearSelector(elementId, attribute, render) {
    const root = document.getElementById(elementId);
    if (!root) return;
    const buttons = root.querySelectorAll('button['+attribute+']');
    function select(period) {
      buttons.forEach(button => {
        const active = button.getAttribute(attribute) === period;
        button.classList.toggle('is-active',active);
        button.setAttribute('aria-pressed',String(active));
      });
      render(period);
    }
    root.addEventListener('click',event => {
      const button=event.target.closest('button['+attribute+']');
      if (button && root.contains(button)) select(button.getAttribute(attribute));
    });
    select('all');
  }

  function initDemographics(data) {
    const source = data.demographics?.years;
    if (!source?.length) return;
    const baseYears=data.years;
    const selected=period=>period==='all'?source:source.filter(x=>x.year===Number(period));
    const sum=(rows,fn)=>rows.reduce((n,row)=>n+Number(fn(row)||0),0);
    function update(period) {
      const rows = selected(period);
      if (!rows.length) return;
      const total = sum(rows,x=>x.genders.male+x.genders.female);
      const male = sum(rows,x=>x.genders.male), female = sum(rows,x=>x.genders.female);
      const adult = sum(rows,x=>x.ages.adult), youth = sum(rows,x=>x.ages.youth);
      const periodLabel=period==='all'?'2022–2026':period+(period==='2026'?' (vorläufig)':'');
      setText('stats-demo-period-a',periodLabel);setText('stats-demo-period-b',periodLabel);
      setText('stats-demo-male',number(male));setText('stats-demo-female',number(female));
      setText('stats-demo-adult',number(adult));setText('stats-demo-youth',number(youth));
      setText('stats-demo-male-pct',pct(male,total));setText('stats-demo-female-pct',pct(female,total));
      setText('stats-demo-adult-pct',pct(adult,total));setText('stats-demo-youth-pct',pct(youth,total));
      setRatioBar('stats-demo-male-bar',male,total,'Männlich: '+number(male)+' von '+number(total)+' ('+pct(male,total)+'); weiblich: '+number(female)+' ('+pct(female,total)+')');
      setRatioBar('stats-demo-adult-bar',adult,total,'Erwachsene: '+number(adult)+' von '+number(total)+' ('+pct(adult,total)+'); Jugend: '+number(youth)+' ('+pct(youth,total)+')');
      setText('stats-demo-total',number(total));
      const matrix=document.querySelectorAll('#stats-demo-matrix .stats-demo-matrix-cell strong');
      const segments=[
        sum(rows,x=>x.gender_age.male_adult),
        sum(rows,x=>x.gender_age.female_adult),
        sum(rows,x=>x.gender_age.male_youth),
        sum(rows,x=>x.gender_age.female_youth)
      ];
      matrix.forEach((item,index)=>{if(index<segments.length)item.textContent=number(segments[index])+' · '+pct(segments[index],total);});
      const categories=[
        {key:'singles',label:'Einzel'},
        {key:'doubles',label:'Doppel'},
        {key:'mixed',label:'Mixed'}
      ].map(c=>({...c,value:sum(rows,x=>x.formats[c.key])}));
      const target=document.getElementById('stats-demo-format-bars');
      if(target){
        target.replaceChildren();
        categories.forEach(c=>target.appendChild(createBar(c.value,total,c.label,pct(c.value,total)+' aller '+number(total)+' Starts')));
      }
    }
    bindYearSelector('stats-demographics-filter','data-demo-period',update);
    const renderYearChart=(targetId,key)=>{
      const target=document.getElementById(targetId);
      if(!target)return;
      target.replaceChildren();
      source.forEach(row=>{
        const full=baseYears.find(y=>y.year===row.year);
        const total=row.genders.male+row.genders.female;
        const value=key==='female'?row.genders.female:row.ages.youth;
        const label=row.year+(full?.partial?' *':'');
        const percent=+pctValue(value,total).toFixed(1);
        target.appendChild(createBar(percent,100,label,number(value)+' von '+number(total)+' Starts ('+pct(value,total)+')',' %'));
      });
    };
    renderYearChart('stats-demo-year-gender','female');
    renderYearChart('stats-demo-year-youth','youth');
  }

  function initMatchBreakdown(data) {
    const years=data.years || [],extra=data.demographics?.years || [];
    if(!years.length || !extra.length)return;
    const combined=years.map(year=>({...year,match_types:extra.find(row=>row.year===year.year)?.match_types}));
    const sum=(rows,key)=>rows.reduce((n,row)=>n+(Number(row[key])||0),0);
    const rowsFor=period=>period==='all'?combined:combined.filter(row=>row.year===Number(period));
    function updateMetric(prefix,count,denominator,label) {
      if(count===null || denominator===0){
        setText(prefix+'-number','–');
        setText(prefix+'-relation','Für diesen Zeitraum nicht getrennt ausgewiesen.');
        const bar=document.getElementById(prefix+'-bar');
        if(bar)bar.style.width='0%';
        return;
      }
      setText(prefix+'-number',number(count));
      setText(prefix+'-relation',number(count)+' von '+number(denominator)+' '+label+' · '+pct(count,denominator));
      const bar=document.getElementById(prefix+'-bar');
      if(bar)bar.style.width=Math.min(100,pctValue(count,denominator))+'%';
    }
    bindYearSelector('stats-match-filter','data-match-period',period=>{
      const selected=rowsFor(period);
      if(!selected.length)return;
      const matches=sum(selected,'matches');
      const mtbs=sum(selected,'match_tiebreaks');
      const twoSet=selected.reduce((n,row)=>n+(row.match_types?.two_set||0),0);
      updateMetric('stats-match-mtb',mtbs,matches,'Matches');
      updateMetric('stats-match-two',twoSet,matches,'Matches');
      const comparable=selected.filter(row=>row.match_types?.retired!=null && row.match_types?.not_started!=null && row.match_types?.scheduled!=null);
      if(comparable.length){
        const retire=comparable.reduce((n,row)=>n+row.match_types.retired,0);
        const played=sum(comparable,'matches');
        const noShow=comparable.reduce((n,row)=>n+row.match_types.not_started,0);
        const planned=comparable.reduce((n,row)=>n+row.match_types.scheduled,0);
        updateMetric('stats-match-retired',retire,played,'gespielten Matches');
        updateMetric('stats-match-no-show',noShow,planned,'Ansetzungen');
      }else{
        updateMetric('stats-match-retired',null,0,'gespielten Matches');
        updateMetric('stats-match-no-show',null,0,'Ansetzungen');
      }
      let note='';
      if(period==='all')note='Für Aufgaben und Nichtantritte sind nur die getrennt erfassten Jahre 2024–2026 enthalten: 39 von 1.075 gespielten Matches endeten durch Aufgabe; 135 von 1.210 Ansetzungen waren Nichtantritte. Match-Tiebreaks: 298 von 1.757 gespielten Matches (17,0 %).';
      else if(period==='2023')note='2023 sind 66 Fälle unter „Aufg. / n.a.“ gemeinsam erfasst. Eine seriöse Aufteilung auf Aufgaben und Nichtantritte ist nicht möglich. Match-Tiebreaks: '+number(mtbs)+' von '+number(matches)+' gespielten Matches ('+pct(mtbs,matches)+').';
      else if(period==='2022')note='2022 wurden Aufgaben und Nichtantritte nicht separat ausgewiesen. Match-Tiebreaks: '+number(mtbs)+' von '+number(matches)+' gespielten Matches ('+pct(mtbs,matches)+').';
      else note='Aufgaben beziehen sich auf gespielte Matches, Nichtantritte auf geplante Ansetzungen. Match-Tiebreaks: '+number(mtbs)+' von '+number(matches)+' gespielten Matches ('+pct(mtbs,matches)+').'+(period==='2026'?' 2026 ist noch vorläufig.':'');
      setText('stats-match-note',note);
    });
  }

  function initTournamentAverages(data) {
    const years=data.years || [];
    function draw(targetId,property,unit){
      const el=document.getElementById(targetId);
      if(!el)return;
      el.replaceChildren();
      const values=years.map(year=>({year,value:year.tournaments?year[property]/year.tournaments:0}));
      const max=Math.max(...values.map(item=>item.value),0);
      values.forEach(item=>{
        const row=years.find(year=>year.year===item.year);
        el.appendChild(createBar(+item.value.toFixed(1),max,item.year+(row.partial?' *':''),
          number(row[property])+' '+unit+' / '+number(row.tournaments)+' Turniere'));
      });
    }
    draw('stats-avg-entries-chart','entries','Starts');
    draw('stats-avg-matches-chart','matches','Matches');
  }

  function initAgeAnalysis(data) {
    const analysis = data.age_analysis;
    const controls = document.getElementById('stats-age-filter');
    if (!analysis || !controls || !Array.isArray(analysis.categories)) return;
    const years = data.years.map(item => item.year);
    const numeric = (category, year) => year === 'all'
      ? category.total
      : (category.by_year[years.indexOf(Number(year))] || 0);
    const byGroup = (name, year) => analysis.categories
      .filter(category => category.group === name)
      .map(category => ({label:category.label,value:numeric(category,year)}))
      .filter(category => category.value > 0)
      .sort((a,b)=>b.value-a.value || a.label.localeCompare(b.label,'de'));
    function bars(targetId, categories) {
      const target = document.getElementById(targetId);
      if (!target) return;
      target.replaceChildren();
      if (!categories.length) {
        const empty=document.createElement('p');
        empty.className='stats-age-empty';
        empty.textContent='Für diesen Zeitraum liegen keine Einträge in diesen Altersklassen vor.';
        target.appendChild(empty);
        return;
      }
      const max=Math.max(...categories.map(item => item.value));
      categories.forEach(item => target.appendChild(createBar(item.value,max,item.label)));
    }
    function setText(id, value) {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    }
    function render(year) {
      const senior = byGroup('AK 30+ (Einzel)',year);
      const youth = byGroup('Jugend (Einzel)',year);
      bars('stats-age-senior-bars',senior);
      bars('stats-age-youth-bars',youth);
      bars('stats-age-active-bars',byGroup('Aktive (Einzel)',year));
      bars('stats-age-doubles-bars',byGroup('Doppel & Mixed',year));
      const bestSenior=senior[0],bestYouth=youth[0];
      const seniorTotal=senior.reduce((sum,category)=>sum+category.value,0);
      setText('age-top-senior', bestSenior?number(bestSenior.value):'–');
      setText('age-top-senior-name', bestSenior?.label||'Keine Einträge');
      setText('age-top-youth', bestYouth?number(bestYouth.value):'–');
      setText('age-top-youth-name', bestYouth?.label||'Keine Einträge');
      setText('age-senior-total', number(seniorTotal));
      const context=year==='all'?'Von 2022 bis 2026':('Im Jahr '+year+(year==='2026'?' (Zwischenstand)':''));
      const insight = bestSenior && bestYouth
        ? context+' führen bei den erfassten Senioren-Einzeln '+bestSenior.label+' ('+number(bestSenior.value)+' Starts), bei den Jugend-Einzeln '+bestYouth.label+' ('+number(bestYouth.value)+' Starts). Die Zahlen spiegeln die ausgeschriebenen Konkurrenzen und die Beteiligung an den ausgewerteten Turnieren wider, nicht eine allgemeine Beliebtheit der AK.'
        : context+' sind nicht für alle Altersklassen Einträge erfasst. Weniger Starts bedeuten nicht automatisch geringeres Interesse.';
      setText('stats-age-insight',insight);
      controls.querySelectorAll('button[data-age-period]').forEach(button=>{
        const active=button.dataset.agePeriod===year;
        button.classList.toggle('is-active',active);
        button.setAttribute('aria-pressed',String(active));
      });
    }
    controls.addEventListener('click',event=>{
      const button=event.target.closest('button[data-age-period]');
      if(!button || !controls.contains(button))return;
      render(button.dataset.agePeriod);
    });
    render('all');
  }

  function initDashboard(data) {
    const page = document.getElementById('stats-dashboard');
    if (!page || !data.years?.length) return;
    const years = data.years;
    const total = prop => years.reduce((sum, row) => sum + (Number(row[prop]) || 0), 0);
    const values = {
      'stats-total-tournaments': total('tournaments'),
      'stats-total-entries': total('entries'),
      'stats-total-matches': total('matches'),
      'stats-total-mtbs': total('match_tiebreaks')
    };
    Object.entries(values).forEach(([id, n]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = number(n);
    });
    renderChart('stats-entries-chart', years, 'entries');
    renderChart('stats-matches-chart', years, 'matches');
    setText('stats-total-mtbs-note',number(total('match_tiebreaks'))+' von '+number(total('matches'))+' Matches ('+pct(total('match_tiebreaks'),total('matches'))+')');
    const mtbTarget = document.getElementById('stats-mtb-chart');
    if (mtbTarget) {
      mtbTarget.replaceChildren();
      years.forEach(year => {
        const percentage = Math.round(year.match_tiebreaks / year.matches * 1000) / 10;
        mtbTarget.appendChild(createBar(percentage, 25, year.year + (year.partial ? ' *' : ''), number(year.match_tiebreaks) + ' von ' + number(year.matches) + ' Matches ('+pct(year.match_tiebreaks,year.matches)+')', ' %'));
      });
    }
    const tbody = document.getElementById('stats-year-table-body');
    if (tbody) {
      tbody.replaceChildren();
      years.slice().reverse().forEach(year => {
        const tr = document.createElement('tr');
        const cells = [
          year.year + (year.partial ? ' *' : ''),
          year.tournaments, number(year.entries), number(year.matches),
          number(year.match_tiebreaks)+' von '+number(year.matches),
          (year.match_tiebreaks / year.matches * 100).toLocaleString('de-DE', {maximumFractionDigits:1}) + ' %'
        ];
        cells.forEach((cell, i) => {
          const el = document.createElement(i === 0 ? 'th' : 'td');
          el.textContent = String(cell);
          if (i === 0) el.scope = 'row';
          tr.appendChild(el);
        });
        tbody.appendChild(tr);
      });
    }
    const event = document.getElementById('stats-event-record');
    if (event && data.featured_event) {
      event.textContent = number(data.featured_event.entries);
    }
    initAgeAnalysis(data);
    initDemographics(data);
    initMatchBreakdown(data);
    initTournamentAverages(data);
    page.dataset.loaded = 'true';
  }

  fetch(dataUrl, {cache:'no-cache'})
    .then(response => { if (!response.ok) throw new Error('Statistikdaten nicht erreichbar'); return response.json(); })
    .then(data => { initHero(data); initDashboard(data); })
    .catch(() => {
      const note = document.getElementById('stats-load-note');
      if (note) note.textContent = 'Die interaktiven Diagramme sind vorübergehend nicht verfügbar. Die Kennzahlen bleiben unten sichtbar.';
    });
})();

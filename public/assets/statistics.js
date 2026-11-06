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
    const mtbTarget = document.getElementById('stats-mtb-chart');
    if (mtbTarget) {
      mtbTarget.replaceChildren();
      years.forEach(year => {
        const percentage = Math.round(year.match_tiebreaks / year.matches * 1000) / 10;
        mtbTarget.appendChild(createBar(percentage, 25, year.year + (year.partial ? ' *' : ''), number(year.match_tiebreaks) + ' von ' + number(year.matches) + ' Matches', ' %'));
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
          number(year.match_tiebreaks),
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

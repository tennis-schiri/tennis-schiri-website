/* Tennis-Schiri: confirmed public nuLiga data for the existing, curated tournament calendar. */
(() => {
  "use strict";
  const URL = "/data/nuliga-tournaments.json";
  let pending;

  async function load() {
    if (!pending) {
      pending = fetch(URL, { credentials: "omit", cache: "no-store" })
        .then(r => r.ok ? r.json() : null)
        .then(data => data && typeof data.events === "object" ? data.events : {})
        .catch(() => ({}));
    }
    return pending;
  }

  function validSource(value) {
    try {
      const url = new URL(value);
      return url.protocol === "https:" &&
        url.hostname === "tvbb.liga.nu" &&
        url.pathname.endsWith("/tournamentCalendar") ? url.href : "";
    } catch (_) { return ""; }
  }

  function fact(card, title, value) {
    if (!value) return;
    let list = card.querySelector(".tournament-details");
    if (!list) {
      list = document.createElement("dl");
      list.className = "tournament-details";
      const meta = card.querySelector(".tournament-meta");
      if (meta) meta.after(list); else card.appendChild(list);
    }
    let item = Array.from(list.querySelectorAll(".tournament-detail")).find(x =>
      x.querySelector("dt")?.textContent?.trim() === title);
    if (!item) {
      item = document.createElement("div");
      item.className = "tournament-detail";
      const term = document.createElement("dt");
      term.textContent = title;
      const description = document.createElement("dd");
      item.append(term, description);
      list.appendChild(item);
    }
    item.querySelector("dd").textContent = value;
  }

  function updateCard(card, record) {
    if (!record || !Array.isArray(record.competitions) || !record.competitions.length) return;
    const comps = record.competitions.filter(x => x && typeof x.name === "string" && x.name.length <= 130);
    if (!comps.length) return;
    const lkValues = [...new Set(comps.map(x => x.lk).filter(Boolean))];
    fact(card, "Konkurrenzen", comps.map(x =>
      x.name + (lkValues.length > 1 && x.lk ? " (LK " + x.lk + ")" : "")
    ).join(" · "));
    if (lkValues.length === 1) fact(card, "LK-Bereich", lkValues[0]);
    if (record.official_title) {
      // Keep the friendly editorial title; expose the official nuLiga title as a title attribute.
      card.querySelector(".tourney-detail-link")?.setAttribute("title", record.official_title);
    }
    const source = validSource(record.source_url);
    const actions = card.querySelector(".event-actions,.tourney-actions");
    if (actions && source && !actions.querySelector(".nuliga-source")) {
      const a = document.createElement("a");
      a.className = "nuliga-source";
      a.textContent = "TVBB-Kalender ↗";
      a.href = source;
      a.rel = "noopener noreferrer";
      a.target = "_blank";
      a.title = "Öffentliche nuLiga-Quelle, geprüft am " + (record.verified_on || "unbekannt");
      actions.appendChild(a);
    }
  }

  async function applyToCards(scope) {
    const events = await load();
    const root = scope || document;
    const cards = root.querySelectorAll("#event-list .tournament-row, #tourney-grid > .tourney-card");
    for (const card of cards) {
      const id = card.dataset.eventId || card.id;
      if (id && Object.prototype.hasOwnProperty.call(events, id)) {
        updateCard(card, events[id]);
      }
    }
    return events;
  }

  async function get(id) {
    const events = await load();
    return Object.prototype.hasOwnProperty.call(events, id) ? events[id] : null;
  }

  window.TSNuLiga = { load, applyToCards, get, validSource };
})();

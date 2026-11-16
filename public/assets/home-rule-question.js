/* Tennis-Schiri – wechselnde Regelfrage auf der Startseite. 
 * Fragen und Antworten aus regelwissen/index.html (neun redaktionelle Praxisfälle).
 * Reihenfolge wird pro Tab gemischt, ohne Wiederholung bis alle Fälle angezeigt wurden.
 */
(() => {
  "use strict";
  const cases = [
  {
    "id": "linienball",
    "title": "Ball auf der Linie – gut oder aus?",
    "situation": "Ein Grundlinienschlag berührt gerade noch die äußere Kante der Linie. Darf der Gegner „Aus“ rufen?",
    "verdict": "Der Ball ist gut.",
    "explanation": "Auch eine nur teilweise berührte Begrenzungslinie gehört zum jeweiligen Spielfeld. Die Ballmarke muss nicht vollständig innerhalb der Linie liegen.",
    "source": "ITF Regel 12"
  },
  {
    "id": "netzaufschlag",
    "title": "Aufschlag berührt das Netz",
    "situation": "Der Aufschlag streift die Netzkante und landet anschließend im richtigen Aufschlagfeld. Wie geht es weiter?",
    "verdict": "Der Aufschlag wird wiederholt.",
    "explanation": "Beim regulären Tennisspiel ist das ein Netzaufschlag (Let). Ein vorheriger erster Aufschlagfehler bleibt bestehen. In ausdrücklich zugelassenen No-Let-Formaten kann eine abweichende Regel gelten.",
    "source": "ITF Regel 22"
  },
  {
    "id": "netzberuehrung",
    "title": "Schläger berührt das Netz",
    "situation": "Beim Volley gerät der Schläger ins Netz, während der Ball noch im Spiel ist. Der Volley landet aber im Feld.",
    "verdict": "Der Spieler verliert den Punkt.",
    "explanation": "Solange der Ball im Spiel ist, dürfen Spieler, Schläger oder mitgeführte Gegenstände das Netz nicht berühren. Ob der Schlag sonst gut war, spielt dabei keine Rolle.",
    "source": "ITF Regel 24 g"
  },
  {
    "id": "fussfehler",
    "title": "Beim Aufschlag auf der Grundlinie",
    "situation": "Beim Aufschlagen berührt ein Fuß während der Aufschlagbewegung die Grundlinie. Ist das erlaubt?",
    "verdict": "Nein – das ist ein Fußfehler.",
    "explanation": "Während der Aufschlagbewegung dürfen die Füße die Grundlinie oder das Spielfeld nicht berühren. Der Verstoß zählt als Aufschlagfehler.",
    "source": "ITF Regel 18"
  },
  {
    "id": "zweiter-aufsprung",
    "title": "Der Ball springt ein zweites Mal auf",
    "situation": "Ein Spieler erwischt den Ball erst, nachdem er auf seiner Seite bereits zweimal aufgesprungen ist.",
    "verdict": "Der Punkt ist verloren.",
    "explanation": "Ein Ball muss zurückgeschlagen werden, bevor er zum zweiten Mal aufspringt. Auch ein danach gelungener Schlag ändert daran nichts.",
    "source": "ITF Regel 24 b"
  },
  {
    "id": "netzreturn",
    "title": "Return mit Netzberührung",
    "situation": "Bei einem normalen Ballwechsel streift der Rückschlag das Netz und fällt anschließend ins richtige Feld.",
    "verdict": "Der Rückschlag ist gut.",
    "explanation": "Anders als beim Netzaufschlag läuft der Ballwechsel weiter, sofern der Ball über das Netz gelangt und korrekt im gegnerischen Feld landet.",
    "source": "ITF Regel 25 a"
  },
  {
    "id": "koerpertreffer",
    "title": "Ball trifft den Spieler",
    "situation": "Während eines Ballwechsels wird ein Spieler von dem Ball getroffen – nicht am Schläger, sondern am Körper.",
    "verdict": "Der getroffene Spieler verliert den Punkt.",
    "explanation": "Trifft der im Spiel befindliche Ball den Spieler oder seine Kleidung statt des Schlägers, ist der Punkt verloren. Das gilt auch, wenn der Treffer unbeabsichtigt war.",
    "source": "ITF Regel 24 i"
  },
  {
    "id": "return-volley",
    "title": "Aufschlag direkt volley zurückspielen?",
    "situation": "Der Rückschläger nimmt einen regulären Aufschlag direkt aus der Luft, bevor der Ball das Aufschlagfeld berührt.",
    "verdict": "Der Rückschläger verliert den Punkt.",
    "explanation": "Ein Aufschlag muss vor dem Rückschlag zunächst aufspringen. Beim anschließenden Ballwechsel sind Volleys dagegen erlaubt.",
    "source": "ITF Regel 24 e"
  },
  {
    "id": "doppel-zwei",
    "title": "Beide Doppelpartner treffen den Ball",
    "situation": "Bei einem Doppel-Rückschlag berühren beide Partner nacheinander den Ball mit ihren Schlägern.",
    "verdict": "Das Doppelteam verliert den Punkt.",
    "explanation": "Im Doppel darf bei einem Rückschlag nicht mehr als ein Spieler den Ball treffen. Wenn beide Partner den Ball berühren, ist der Punkt verloren.",
    "source": "ITF Regel 24 l"
  }
];
  const card = document.querySelector("#regelwissen .rules-art");
  if (!card) return;
  const title = card.querySelector(".rules-question");
  const situation = card.querySelector(".rules-home-situation");
  const answer = card.querySelector(".rules-home-answer");
  const verdict = answer?.querySelector(".rules-home-verdict");
  const explanation = answer?.querySelector(".rules-home-explanation");
  const link = answer?.querySelector(".rules-home-case-link");
  const source = answer?.querySelector(".rules-home-source");
  const button = card.querySelector(".rules-next-question");
  if (!title || !situation || !answer || !verdict || !explanation || !link || !source || !button) return;

  const storageKey = "ts-rule-question-bag-v1";
  const ids = cases.map(item => item.id);
  const byId = new Map(cases.map(item => [item.id, item]));
  const shuffled = (input) => {
    const arr = input.slice();
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  };
  let bag = [];
  let previous = null;
  try {
    const saved = JSON.parse(sessionStorage.getItem(storageKey) || "{}");
    if (ids.includes(saved.last)) previous = saved.last;
    if (Array.isArray(saved.remaining) && saved.remaining.length &&
        saved.remaining.every(id => ids.includes(id)) &&
        new Set(saved.remaining).size === saved.remaining.length) {
      bag = saved.remaining;
    }
  } catch (_) {
    // Session storage may be unavailable (private browsing); randomization still works.
  }

  function nextQuestion() {
    if (!bag.length) bag = shuffled(ids);
    if (bag.length > 1 && bag[0] === previous) {
      const other = 1 + Math.floor(Math.random() * (bag.length - 1));
      [bag[0], bag[other]] = [bag[other], bag[0]];
    }
    const item = byId.get(bag.shift());
    if (!item) return;
    previous = item.id;
    try {
      sessionStorage.setItem(storageKey, JSON.stringify({last: previous, remaining: bag}));
    } catch (_) {}
    answer.open = false; // A new question always starts without a revealed answer.
    title.textContent = item.title;
    situation.textContent = item.situation;
    verdict.textContent = item.verdict;
    explanation.textContent = item.explanation;
    source.textContent = item.source;
    link.href = "regelwissen/#" + encodeURIComponent(item.id);
  }

  button.addEventListener("click", nextQuestion);
  nextQuestion();
})();

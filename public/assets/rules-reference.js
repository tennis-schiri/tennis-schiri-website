/* Offizielle Regelwerke: thematischer Suchindex und geprüfte Kurzantworten.
   Ausschließlich Quellenlinks; keine Kopien der vollständigen Verbands-PDFs. */
(function(){
'use strict';
const DTB='https://www.tennis.de/content/dam/tennis/dtb/Verband/dokumente/satzungen-und-ordnungen/Organisation/';
const pdf=(name)=>DTB+name+'.pdf.coredownload.inline.pdf';
const docs=[
 {id:'itf',name:'Tennisregeln der ITF',issuer:'ITF / DTB',category:'spielregeln',version:'DTB-Ausgabe 09.11.2025',url:pdf('Tennisregeln%20der%20ITF'),description:'Spielablauf, Aufschlag, Zählweise, Ball im Spiel, Fehler, Doppel und Schiedsrichterentscheidungen.',terms:'linienball aus aufschlag tiebreak tie-break netz fussfehler beratung punkte volley doppelfehler'},
 {id:'itf-en',name:'ITF Rules of Tennis 2026 (English)',issuer:'ITF',category:'spielregeln',version:'2026 · englisches Original',url:'https://www.itftennis.com/media/7221/2026-rules-of-tennis-english.pdf',description:'Englischer Originaltext der Spielregeln für das Jahr 2026, einschließlich offizieller Klarstellungen.',terms:'foot fault rules tennis singles doubles score serve'},
 {id:'turnier',name:'DTB-Turnierordnung',issuer:'DTB',category:'turnier',version:'DTB-Ausgabe 09.11.2025',url:pdf('DTB%20Turnierordnung'),description:'Genehmigung, Teilnahme, Ausschreibung, Nennung, Auslosung, Sign-In, Turnierleitung und Spielplan.',terms:'ausschreibung nenngeld turnier abmeldung auslosung teilnehmer losung turnierveranstalter'},
 {id:'lk',name:'DTB-Richtlinien für LK-Turniere',issuer:'DTB',category:'turnier',version:'DTB-Dokumentenstand 2026',url:pdf('DTB%20Richtlinien%20f%C3%BCr%20LK-Turniere'),description:'Planung, Genehmigung, Vorbereitung, Durchführung und Ergebnisdokumentation von LK-Turnieren.',terms:'leistungsklasse anmeldung lk turnier richtlinien genehmigung'},
 {id:'lko',name:'DTB-Leistungsklassenordnung',issuer:'DTB',category:'wertung',version:'DTB-Ausgabe 09.11.2025',url:pdf('DTB%20Leistungsklassenordnung'),description:'Grundlagen der Leistungsklassen, Zuständigkeiten, Berechnung und rechtliche Einordnung.',terms:'lk berechnung spielstärke id nummer leistungsklasse einstufung'},
 {id:'lko-dfb',name:'DTB-Durchführungsbestimmungen zur LKO',issuer:'DTB',category:'wertung',version:'DTB-Dokumentenstand 2026',url:pdf('DTB%20Durchf%C3%BChrungsbestimmungen%20zur%20LKO'),description:'Ergänzende technische Bestimmungen zur Berechnung und Einordnung der Leistungsklassen.',terms:'lk berechnung punkte abwertung altersfaktor leistungszuschlag'},
 {id:'rangliste',name:'DTB-Ranglistenordnung',issuer:'DTB',category:'wertung',version:'DTB-Ausgabe 09.11.2025',url:pdf('DTB%20Ranglistenordnung'),description:'Regeln zur deutschen Rangliste und ihrer Auswertung.',terms:'rangliste dtb ranking a4 s3 s4'},
 {id:'wettspiel',name:'DTB-Wettspielordnung',issuer:'DTB',category:'mannschaft',version:'Gültig ab Winter 2026/27',url:pdf('DTB%20Wettspielordnung'),description:'Rahmenregeln für Mannschaftswettbewerbe, Spielberechtigung und offizielle Wettspiele.',terms:'mannschaft punktspiel lizenz spielberechtigung namentliche meldung'},
 {id:'ohne-schiri',name:'Richtlinien für das Spiel ohne Schiedsrichter',issuer:'DTB',category:'praxis',version:'DTB-Ausgabe 09.11.2025',url:pdf('DTB%20Richtlinien%20f%C3%BCr%20das%20Spiel%20ohne%20Schiedsrichter'),description:'Linienentscheidungen, Ballabdrücke auf Sand, Spielstand-Streit und Verfahren ohne Stuhlschiedsrichter.',terms:'aus ballabdruck linie streit spielstand osr ohne schiedsrichter'},
 {id:'kodex',name:'DTB-Verhaltenskodex',issuer:'DTB',category:'praxis',version:'DTB-Ausgabe 09.11.2025',url:pdf('DTB%20Verhaltenskodex'),description:'Verhaltensregeln und mögliche Sanktionen im Turnierbetrieb.',terms:'strafe verwarnung disziplin verhalten schläger missbrauch zeitüberschreitung'},
 {id:'tvbb',name:'TVBB – Ordnungen und Downloads',issuer:'TVBB',category:'regional',version:'Aktuelle Fassungen beim TVBB prüfen',url:'https://tvbb.de/verband/',description:'Offizielle Sammlung für Berlin-Brandenburg: TVBB-Turnierordnung, Wettspielordnung und Durchführungsbestimmungen.',terms:'berlin brandenburg regional tvbb liga verein turnierordnung'},
 {id:'dtb-hub',name:'DTB – Dokumente und Ordnungen',issuer:'DTB',category:'sammlung',version:'Offizielle, laufend gepflegte Übersicht',url:'https://www.tennis.de/dtb/verband/dokumente.html',description:'Zentrale Downloadseite zur Kontrolle des neuesten Stands aller DTB-Ordnungen.',terms:'alle aktuell ordnungen dtb pdf dokumente'}
];
const itfNames=['Spielfeld','Ständige Einrichtungen','Bälle','Schläger','Zählweise in einem Spiel','Zählweise in einem Satz','Zählweise in einem Wettspiel','Aufschläger und Rückschläger','Wahl der Seiten und des Aufschlags','Wechsel der Spielfeldseiten','Ball im Spiel','Ball berührt eine Linie','Ball berührt eine ständige Einrichtung','Reihenfolge beim Aufschlag','Reihenfolge beim Rückschlag im Doppel','Aufschlag','Ausführung des Aufschlags','Fußfehler','Aufschlagfehler','Zweiter Aufschlag','Spielbereitschaft','Wiederholung des Aufschlags','Wiederholungen','Punktverlust','Guter Rückschlag','Behinderung','Berichtigung von Irrtümern','Verantwortlichkeiten der Platz-Offiziellen','Kontinuierliches Spiel','Beratung','Technik für Spieler-Analysen'];
const toNames=['Geltungsbereich','Bekämpfung des Dopings','Verbot von Wettspielmanipulationen','Turnierarten','Kategorien','Altersklassen/Jahrgänge','Genehmigung','Anmeldung','Ergebnismeldung','Einschränkung des Teilnehmerkreises','Teilnahmeberechtigung','Aufgaben des Veranstalters','Ehrenausschuss','Turnierausschuss','Turnierleiter','Oberschiedsrichter','Schiedsrichter','Linienrichter','Ausschreibungspflicht','Inhalt der Ausschreibung','Abgabe der Nennung','Gleichzeitige Turniere','Zurückziehen der Nennung nach der Auslosung','Nenngeld','Teilnehmer','Feststellung der Spielstärke','Anwesenheitsliste (Sign-In)','Qualifikation','Hauptfeld','Setzung','Rasten','Durchführung der Auslosung','Ausfall von Teilnehmern','Änderung der Setzung von Teilnehmern','Doppel','Mindestteilnehmerzahl','Spielregeln','Spielkleidung, Werbung','Bälle','Spielplan','Preisgeld','Vergabe der Deutschen Meisterschaften und Turnierserien','Zusammensetzung des Turnierausschusses','Teilnahmeberechtigung an Nationalen Meisterschaften','Ergänzende Bestimmungen für Jugendturniere','Disziplinarordnung','Einspruch','Beschwerde','Änderungen'];
const topics=[
 ...itfNames.map((title,i)=>({doc:'itf',section:'Regel '+(i+1),title,extra:i===11?'Linienball gut oder aus Ballabdruck':i===17?'Fußfehler Grundlinie':i===21?'Netzaufschlag Let':i===23?'Ball trifft Körper Netzberührung zweiter Aufsprung Doppel':i===29?'Coaching Trainer beraten':''})),
 ...toNames.map((title,i)=>({doc:'turnier',section:'§ '+(i+1),title,extra:i===23?'Nenngeld Rücktritt Zahlung':i===26?'Sign In Anwesenheit Meldeschluss':i===31?'Auslosung Setzung auslosen':''})),
 {doc:'lk',section:'Abschnitt 1',title:'Einleitung und Geltungsbereich',extra:'Wer darf ein LK Turnier durchführen'},
 {doc:'lk',section:'Abschnitt 2',title:'Turnierplanung',extra:'Turnierterminplanung'},
 {doc:'lk',section:'Abschnitt 3',title:'Turnieranmeldung und Genehmigungsverfahren',extra:'Genehmigung Veranstalter'},
 {doc:'lk',section:'Abschnitt 4',title:'Teilnehmer',extra:'Spieler Meldung'},
 {doc:'lk',section:'Abschnitt 5',title:'Turniervorbereitung',extra:'Vorbereitung Organisation'},
 {doc:'lk',section:'Abschnitt 6',title:'Turnierdurchführung',extra:'Wettspiel Leitung'},
 {doc:'lk',section:'Abschnitt 7',title:'Ergebnisdokumentation',extra:'Ergebnis Meldung'},
 {doc:'lko',section:'§ 1',title:'Geltungsbereich',extra:'ID-Nummer Mitgliedschaft'},
 {doc:'lko',section:'§ 4',title:'LK-Berechnung',extra:'Leistungsklasse Berechnung'},
 {doc:'ohne-schiri',section:'Richtlinie',title:'Linienball ohne Schiedsrichter',extra:'Hartplatz Fehlentscheidung'},
 {doc:'ohne-schiri',section:'Richtlinie',title:'Ballabdruck und Linienball auf Sand',extra:'Asche Sandplatz Abdruck Streit'},
 {doc:'ohne-schiri',section:'Richtlinie',title:'Spielstand-Diskussion',extra:'Zählstand unklar'},
 {doc:'kodex',section:'Verhaltenskodex',title:'Verhalten und Sanktionen',extra:'Verwarnung Strafe Code violation'},
 {doc:'wettspiel',section:'§ 4',title:'Spielberechtigung',extra:'Mannschaft Spielberechtigung'},
 {doc:'tvbb',section:'TVBB',title:'TVBB-Wettspielordnung und Durchführungsbestimmungen',extra:'Mannschaftswettkampf Berlin Brandenburg'},
 {doc:'tvbb',section:'TVBB',title:'TVBB-Turnierordnung',extra:'Regionale Turnierbestimmungen Berlin Brandenburg'}
];
const answers=[
 {prompt:'Ist ein Ball auf der Linie gut oder aus?',keywords:'linie ball linienball berührt rand aus gut',response:'Berührt der Ball die zum Spielfeld gehörende Linie, ist er gut. Bei einem Streit über einen Sandplatz-Ballabdruck kommen zusätzlich die DTB-Richtlinien für das Spiel ohne Schiedsrichter zum Tragen.',refs:[['itf','Regel 12'],['ohne-schiri','Ballabdruck und Linienball']]},
 {prompt:'Was passiert bei einem Netzaufschlag?',keywords:'netzaufschlag aufschlag netz let wiederholen',response:'Berührt der Aufschlag das Netz und landet anschließend im richtigen Aufschlagfeld, wird er nach der regulären ITF-Spielweise wiederholt. Bei einer ausdrücklich zugelassenen No-Let-Regel kann das abweichen.',refs:[['itf','Regel 22']]},
 {prompt:'Wann liegt ein Fußfehler vor?',keywords:'fußfehler fussfehler aufschlag grundlinie fuß tritt berühren',response:'Während der Aufschlagbewegung darf die Grundlinie beziehungsweise das Spielfeld nicht mit dem Fuß berührt werden. Eine solche Berührung kann einen Fußfehler begründen. Die ITF hat für 2026 außerdem eine Klarstellung zu Doppel-Aufschlägen aufgenommen.',refs:[['itf','Regel 18'],['itf-en','Rule 18']]},
 {prompt:'Wann wird beim Tie-Break die Seite gewechselt?',keywords:'tiebreak tie-break seitenwechsel sechs 6 punkte',response:'Beim regulären Tie-Break werden nach jeweils sechs gespielten Punkten die Seiten gewechselt. Zugelassene alternative Zählweisen können Besonderheiten vorsehen.',refs:[['itf','Regel 10']]},
 {prompt:'Darf der Schläger das Netz berühren?',keywords:'netz berühren schläger volley punkteverlust',response:'Berührt ein Spieler oder sein Schläger das Netz, während der Ball noch im Spiel ist, verliert dieser Spieler den Punkt.',refs:[['itf','Regel 24']]},
 {prompt:'Wie ist ein zweiter Ballaufsprung zu entscheiden?',keywords:'zweimal aufspringen zweiter aufsprung bounce',response:'Ein Rückschlag muss erfolgen, bevor der Ball auf der eigenen Seite ein zweites Mal aufspringt. Geschieht das nicht, geht der Punkt verloren.',refs:[['itf','Regel 24']]},
 {prompt:'Darf ein Aufschlag direkt volley returniert werden?',keywords:'return volley aufschlag vor aufspringen returniert',response:'Nein. Der Rückschläger darf einen Aufschlag nicht vor dem Aufspringen zurückspielen; sonst verliert er den Punkt.',refs:[['itf','Regel 24']]},
 {prompt:'Wer entscheidet über einen Ballabdruck ohne Schiedsrichter?',keywords:'ballabdruck schiedsrichter sandplatz linienentscheidung streit osr',response:'Bei Spielen ohne Stuhlschiedsrichter sind die DTB-Richtlinien maßgeblich. Auf Sand kann ein Offizieller bei Streit um einen Ballabdruck hinzugezogen werden; die Entscheidung hängt unter anderem davon ab, ob der Abdruck eindeutig identifiziert werden kann.',refs:[['ohne-schiri','Ballabdruck und Linienball auf Sand']]},
 {prompt:'Wo finde ich Regeln zur Auslosung und Setzung?',keywords:'auslosung setzen setzliste sign in turnier feld',response:'Die DTB-Turnierordnung enthält die Regelungen zur Setzung (§ 30), zur Anwesenheitsliste (§ 27) sowie zur Durchführung der Auslosung (§ 32). Für konkrete Fristen gilt zusätzlich die jeweilige Ausschreibung.',refs:[['turnier','§§ 27, 30, 32']]},
 {prompt:'Was gilt beim Nenngeld oder einem Rückzug?',keywords:'nenngeld zurückziehen rückzug abmelden absage erstattung nach auslosung',response:'Die DTB-Turnierordnung behandelt das Nenngeld in § 24 sowie das Zurückziehen einer Nennung nach der Auslosung in § 23. Ob und in welcher Höhe etwas zu zahlen ist, muss anhand des einschlägigen Paragraphen und der Ausschreibung geprüft werden.',refs:[['turnier','§§ 23–24']]},
 {prompt:'Welche Angaben gehören in eine Turnierausschreibung?',keywords:'ausschreibung inhalt pflicht angaben kosten fristen',response:'Die Ausschreibungspflicht und die erforderlichen Inhalte sind in der DTB-Turnierordnung in §§ 19–20 geregelt. Zusätzlich können die Richtlinien für LK-Turniere und regionale Durchführungsbestimmungen relevant sein.',refs:[['turnier','§§ 19–20'],['lk','Abschnitt 5']]},
 {prompt:'Wer darf ein LK-Turnier veranstalten?',keywords:'lk turnier ausrichten veranstalter verein genehmigung lizenz',response:'LK-Turniere dürfen nur von den nach den DTB-Richtlinien berechtigten beziehungsweise genehmigten Veranstaltern organisiert werden. Im TVBB sind ergänzende regionale Vorgaben möglich; die Genehmigung ist beim zuständigen Verband zu prüfen.',refs:[['lk','Abschnitte 1 und 3'],['tvbb','Regionale Bestimmungen']]},
 {prompt:'Wo finde ich Vorschriften zur LK-Berechnung?',keywords:'leistungsklasse lk berechnung berechnen berechnet berechne verbesserung altersfaktor punkte',response:'Die grundsätzlichen Vorgaben stehen in der DTB-Leistungsklassenordnung, insbesondere § 4. Einzelheiten zur Berechnung und den Einflussgrößen regeln die Durchführungsbestimmungen zur LKO.',refs:[['lko','§ 4'],['lko-dfb','Durchführungsbestimmungen']]},
 {prompt:'Welche Regeln gelten beim Mannschaftsspiel in Berlin-Brandenburg?',keywords:'mannschaft tvbb wettspielordnung mannschaftsspiel liga punktspiel berlin brandenburg',response:'Für den Mannschaftsspielbetrieb im TVBB sind die aktuelle TVBB-Wettspielordnung und die dazugehörigen Durchführungsbestimmungen wichtig. Je nach Wettbewerb gelten ergänzend die DTB-Wettspielordnung und die ITF-Spielregeln.',refs:[['tvbb','Wettspielordnung und Durchführungsbestimmungen'],['wettspiel','DTB-Wettspielordnung']]},
 {prompt:'Wer ist bei einem Turnier für Regelfragen zuständig?',keywords:'oberschiedsrichter entscheidung turnierleiter streit protest turnierorgane',response:'Die Aufgaben von Turnierleiter und Oberschiedsrichter werden in der DTB-Turnierordnung unter anderem in §§ 15–16 beschrieben. ITF-Regel 28 behandelt die Verantwortlichkeiten der Platz-Offiziellen.',refs:[['turnier','§§ 15–16'],['itf','Regel 28']]},
 {prompt:'Was gilt bei unsportlichem Verhalten?',keywords:'verhaltenskodex schläger werfen schimpfen beleidigen verwarnung strafe',response:'Der DTB-Verhaltenskodex enthält die Regeln und möglichen Sanktionen für Fehlverhalten. Die Entscheidung im konkreten Fall hängt vom Sachverhalt und den anwendbaren Bestimmungen ab.',refs:[['kodex','Verhaltenskodex']]},
 {prompt:'Dürfen im Doppel beide Spieler den Ball treffen?',keywords:'doppel beide spieler berühren schläger doppelteam',response:'Trifft beim Rückschlag mehr als ein Spieler des Doppelpaares den Ball, verliert das Doppelteam den Punkt.',refs:[['itf','Regel 24']]},
 {prompt:'Wann darf ein Spieler beraten werden?',keywords:'coaching trainer beraten hilfe signal zuruf spieler',response:'Ob Beratung während des Wettspiels erlaubt ist, richtet sich nach der ITF-Regel 30 und den für den Wettbewerb geltenden Zusatzbestimmungen. Ein pauschales Ja oder Nein ohne Turnierkontext wäre nicht zuverlässig.',refs:[['itf','Regel 30'],['turnier','§ 37']]}
];
const norm=value=>String(value||'').toLocaleLowerCase('de-DE').normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/ß/g,'ss').replace(/[^a-z0-9]+/g,' ').trim();
const STOP=new Set(['was','wie','wo','wer','welche','welcher','welches','ist','sind','darf','dürfen','kann','bei','im','am','an','auf','und','oder','ein','eine','einer','einem','einen','dem','den','der','die','das','des','zu','zum','zur','mit','ich','mir','mich','es','man','wenn','wird','werden','gilt','gelten','für','fuer','nach','vor','um','hier','welchen','welchem','ist','muss','müssen','soll','sollen','nicht','in','aus','beim','einem','einer'].map(norm));
function tokens(value){return norm(value).split(' ').filter(t=>(t.length>2||t==='lk'||/^\d+$/.test(t))&&!STOP.has(t));}
const hasAll=(hay,terms)=>terms.every(t=>hay.includes(t));
function rank(query,searchable){
 const phrase=norm(query),hay=norm(searchable);
 const words=tokens(query);
 if(!words.length)return 0;
 let score=0;
 for(const w of words){if(hay.includes(w))score+=w.length>=6?3:2;}
 if(hay.includes(phrase))score+=6;
 return score;
}
const docMap=new Map(docs.map(d=>[d.id,d]));
function node(tag,cl,content){
 const n=document.createElement(tag);if(cl)n.className=cl;
 if(content!==undefined)n.textContent=content;
 return n;
}
function addLink(parent,label,href,className){
 const a=node('a',className,label);a.href=href;a.target='_blank';a.rel='noopener noreferrer';parent.append(a);return a;
}
function drawDocuments(query='',category='alle'){
 const output=document.getElementById('rulebooks-grid');
 if(!output)return;
 output.replaceChildren();
 const filtered=docs.filter(d=>(category==='alle'||d.category===category)&&(!query||rank(query,[d.name,d.issuer,d.description,d.terms,d.version].join(' '))>0));
 for(const d of filtered){
  const box=node('article','rulebook-card');
  const top=node('div','rulebook-card-top');
  top.append(node('span','rulebook-issuer',d.issuer),node('span','rulebook-year',d.version));
  box.append(top,node('h3','',d.name),node('p','',d.description));
  addLink(box,'Originaldokument öffnen ↗',d.url,'rulebook-open');
  output.append(box);
 }
 const counter=document.getElementById('rulebooks-count');
 if(counter)counter.textContent=filtered.length+' von '+docs.length+' Dokumenten';
 const no=document.getElementById('rulebooks-empty');if(no)no.hidden=filtered.length!==0;
}
function drawTopics(query){
 const output=document.getElementById('rules-topics-results');
 if(!output)return;
 output.replaceChildren();
 if(!query){output.hidden=true;return;}
 const result=topics.map(t=>({t,score:rank(query,[t.title,t.section,t.extra,docMap.get(t.doc)?.name||''].join(' '))})).filter(x=>x.score>0).sort((a,b)=>b.score-a.score).slice(0,7);
 output.hidden=false;
 output.append(node('h3','rules-topics-heading',result.length?'Passende Paragraphen und Themen':'Keine passenden Regelstellen'));
 for(const {t} of result){
  const d=docMap.get(t.doc);
  const a=node('a','rules-topic-hit');
  a.href=d.url;a.target='_blank';a.rel='noopener noreferrer';
  a.append(node('strong','',t.section+' · '+t.title),node('small','',d.name+' ↗'));
  output.append(a);
 }
 if(!result.length)output.append(node('p','rules-topic-empty','Versuche ein anderes Stichwort oder öffne eines der Originaldokumente.'));
}
function matchAnswer(query){
 const qs=tokens(query);
 if(!qs.length)return null;
 const ranked=answers.map(a=>{
  const keys=new Set(tokens(a.keywords));
  const direct=qs.filter(q=>keys.has(q)).length;
  const related=qs.filter(q=>[...keys].some(k=>k.length>4&&q.length>4&&(k.startsWith(q)||q.startsWith(k)))).length;
  return {a,score:direct*4+related,matched:direct+related};
 }).sort((a,b)=>b.score-a.score);
 // Unknown or ambiguous questions must not receive an invented rule decision.
 if(!ranked[0]||ranked[0].score<4)return null;
 const best=ranked[0],runner=ranked[1];
 if(qs.length>1&&best.matched<2)return null;
 if(qs.length===1&&qs[0].length<6)return null;
 if(runner&&best.score-runner.score<3)return null;
 return best.a;
}
function ask(value){
 const result=document.getElementById('rules-ask-result');
 if(!result)return;
 result.replaceChildren();
 result.hidden=false;
 const a=matchAnswer(value);
 if(!a){
  result.append(node('div','rules-answer-kicker','KEINE SICHERE KURZANTWORT'));
  result.append(node('p','rules-answer-warning','Für diese Frage liegt noch keine eindeutig passende redaktionell geprüfte Kurzantwort vor. Hier sind passende Fundstellen, die du im offiziellen Dokument nachlesen kannst:'));
  const candidates=topics.map(t=>({t,score:rank(value,[t.title,t.section,t.extra,docMap.get(t.doc)?.name||''].join(' '))})).filter(x=>x.score>0).sort((a,b)=>b.score-a.score).slice(0,4);
  const refs=node('div','rules-answer-sources');
  for(const {t} of candidates){const d=docMap.get(t.doc);if(d)addLink(refs,d.name+' · '+t.section+': '+t.title+' ↗',d.url,'rules-answer-source');}
  if(!candidates.length)addLink(refs,'Offizielle DTB-Dokumente öffnen ↗',docMap.get('dtb-hub').url,'rules-answer-source');
  result.append(refs);
  drawTopics(value);return;
 }
 result.append(node('div','rules-answer-kicker','KURZANTWORT AUS DER GEPRÜFTEN SAMMLUNG'));
 result.append(node('h3','',a.prompt));
 result.append(node('p','',a.response));
 const sources=node('div','rules-answer-sources');
 sources.append(node('strong','','Quellen:'));
 for(const [doc,clause] of a.refs){const d=docMap.get(doc);if(d)addLink(sources,d.name+' · '+clause+' ↗',d.url,'rules-answer-source');}
 result.append(sources);
 drawTopics(value);
}
function init(){
 const input=document.getElementById('rulebooks-search');
 const category=document.getElementById('rulebooks-filter');
 const form=document.getElementById('rules-ask-form');
 const question=document.getElementById('rules-ask-input');
 if(input&&category){
  const update=()=>{drawDocuments(input.value,category.value);drawTopics(input.value);};
  input.addEventListener('input',update);
  category.addEventListener('change',update);
  update();
 }
 if(form&&question)form.addEventListener('submit',e=>{
  e.preventDefault();const val=question.value.trim();
  if(val.length<3)return;ask(val);
  document.getElementById('rules-ask-result')?.scrollIntoView({behavior:'smooth',block:'nearest'});
 });
 document.querySelectorAll('[data-rule-question]').forEach(button=>button.addEventListener('click',()=>{
  if(!question)return;
  const val=button.getAttribute('data-rule-question')||'';
  question.value=val;ask(val);
  document.getElementById('rules-ask-result')?.scrollIntoView({behavior:'smooth',block:'nearest'});
 }));
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();

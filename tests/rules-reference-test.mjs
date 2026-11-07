// Regression test for the source-grounded official tennis-rules library.
import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';

const source=fs.readFileSync('public/assets/rules-reference.js','utf8');
const tail=/\}\)\(\);\s*$/;
assert.ok(tail.test(source),'Expected rules-library IIFE');
const instrumented=source.replace(tail,'globalThis.__test={docs,topics,answers,matchAnswer};})();');
const context={document:{readyState:'loading',addEventListener:()=>{}}};
vm.runInNewContext(instrumented,context,{filename:'rules-reference.js',timeout:1500});
const {docs,topics,answers,matchAnswer}=context.__test;
assert.ok(docs.length>=12,'Expected twelve official documents');
assert.ok(topics.length>=90,'Expected searchable paragraph index');
assert.ok(answers.length>=15,'Expected curated rules answers');
assert.equal(new Set(docs.map(d=>d.id)).size,docs.length);
for(const doc of docs){
 assert.ok(/^https:\/\//.test(doc.url),'Not an HTTPS source: '+doc.id);
 assert.ok(/(?:tennis\.de|tvbb\.de|itftennis\.com)/.test(new URL(doc.url).hostname),'Unexpected source host: '+doc.url);
 assert.ok(doc.version&&doc.issuer&&doc.description,'Incomplete source: '+doc.id);
}
for(const topic of topics){
 assert.ok(docs.some(d=>d.id===topic.doc),'Unknown rulebook in topic '+topic.title);
}
const questions=[
 ['Ist ein Ball auf der Linie gut oder aus?','Ist ein Ball auf der Linie'],
 ['Was gilt beim Nenngeld oder einem Rückzug?','Nenngeld'],
 ['Wie werden LK berechnet?','LK-Berechnung'],
 ['Wann wird beim Tie-Break die Seite gewechselt?','Tie-Break']
];
for(const [q,expected] of questions){
 const answer=matchAnswer(q);
 assert.ok(answer, 'Expected a safe answer: '+q);
 assert.ok(answer.prompt.includes(expected),'Wrong response for '+q+': '+answer.prompt);
}
for(const q of ['Welche Farbe hat der Schiedsrichterstuhl?','Kann ich auf einem Basketballfeld spielen?']){
 assert.equal(matchAnswer(q),null,'Unknown questions must not receive fabricated answers: '+q);
}
const html=fs.readFileSync('public/regelwissen/index.html','utf8');
for(const id of ['regelwerke','rulebooks-grid','rulebooks-search','rulebooks-filter','rules-ask-form','rules-ask-result','rules-ask-input']){
 assert.ok(html.includes('id="'+id+'"'),'Missing rules UI element: '+id);
}
const home=fs.readFileSync('public/index.html','utf8');
assert.ok(home.includes('regelwissen/#regelwerke'),'Homepage link to rules library missing');
console.log('Rulebooks OK:',docs.length,'official links;',topics.length,'searchable topics;',answers.length,'curated answers.');

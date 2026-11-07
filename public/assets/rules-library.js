/* Regelfälle: Kategorie- und Textfilter ohne externe Dienste. */
(function(){
 'use strict';
 const buttons=Array.from(document.querySelectorAll('.rules-filter'));
 const cards=Array.from(document.querySelectorAll('.rules-case'));
 const input=document.getElementById('rules-search');
 const count=document.getElementById('rules-count');
 const empty=document.getElementById('rules-empty');
 if(!buttons.length||!cards.length||!input)return;
 let category='alle';
 function refresh(){
  const query=input.value.trim().toLocaleLowerCase('de-DE');
  let shown=0;
  for(const card of cards){
   const allowed=category==='alle'||card.dataset.category===category;
   const matches=!query||card.textContent.toLocaleLowerCase('de-DE').includes(query);
   card.hidden=!(allowed&&matches);
   if(!card.hidden)shown++;
  }
  count.textContent=shown===1?'1 Regelfall':shown+' Regelfälle';
  empty.hidden=shown!==0;
 }
 buttons.forEach(button=>button.addEventListener('click',()=>{
  category=button.dataset.filter;
  buttons.forEach(item=>item.setAttribute('aria-pressed',String(item===button)));
  refresh();
 }));
 input.addEventListener('input',refresh);
 refresh();
})();

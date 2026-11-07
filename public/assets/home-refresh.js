/* Tennis-Schiri homepage: opens the compact workshop registration only on demand. */
(function(){
 'use strict';
 const target=document.getElementById('regelworkshop-anmeldung');
 if(!target)return;
 function reveal(){
   if(location.hash==='#regelworkshop-anmeldung'||location.hash==='#workshop-signup-home-title'){
     target.open=true;
     requestAnimationFrame(()=>target.scrollIntoView({block:'start'}));
   }
 }
 window.addEventListener('hashchange',reveal);
 document.addEventListener('click',event=>{
   const anchor=event.target.closest('a[href="#regelworkshop-anmeldung"],a[href="#workshop-signup-home-title"]');
   if(anchor&&location.hash===(anchor.getAttribute('href')))reveal();
 });
 reveal();
})();

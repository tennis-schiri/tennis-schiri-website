/* © Tennis-Schiri 2026 · schnell editierbare Workshop-Begrüßungsfolie.
   Speichert Terminangaben nur im Browser des Referenten.
   Bei Live-Raum werden sie separat an die Teilnehmer synchronisiert. */
'use strict';
(function(){
  const KEY='ts-workshop-welcome-v1';
  const fields=['verein','ort','datum','uhrzeit'];
  const limit={verein:85,ort:85,datum:10,uhrzeit:5};
  const clean=value=>typeof value==='string'?value.trim().replace(/\s+/g,' '):'';
  const normalize=input=>{
    const out={};
    for(const key of fields){
      let value=clean(input?.[key]||'').slice(0,limit[key]);
      if(key==='datum'&&value&&!/^\d{4}-\d{2}-\d{2}$/.test(value))value='';
      if(key==='uhrzeit'&&value&&!/^([01]\d|2[0-3]):[0-5]\d$/.test(value))value='';
      out[key]=value;
    }
    return out;
  };
  let welcome=normalize({});
  try{welcome=normalize(JSON.parse(localStorage.getItem(KEY)||'{}'))}catch(e){}
  const copy=()=>({...welcome});
  const install=(toolbar,onSaved)=>{
    if(document.getElementById('edit-welcome-btn'))return;
    const btn=document.createElement('button');
    btn.type='button';btn.id='edit-welcome-btn';btn.textContent='✎ Startfolie bearbeiten';
    btn.title='Verein, Ort, Datum und Uhrzeit für diesen Workshop festlegen';
    toolbar.insertBefore(btn,toolbar.querySelector('#full-btn'));
    const overlay=document.createElement('div');overlay.className='welcome-editor-overlay';overlay.hidden=true;
    const dialog=document.createElement('section');dialog.className='welcome-editor-box';
    dialog.setAttribute('role','dialog');dialog.setAttribute('aria-modal','true');
    dialog.setAttribute('aria-labelledby','welcome-editor-heading');
    const title=document.createElement('h2');title.className='welcome-editor-heading';
    title.id='welcome-editor-heading';title.textContent='Startfolie bearbeiten';
    const desc=document.createElement('p');desc.className='welcome-editor-description';
    desc.textContent='Diese Angaben erscheinen auf der ersten Folie. Du kannst sie jederzeit vor oder während des Workshops ändern.';
    dialog.append(title,desc);
    const form=document.createElement('form');form.id='welcome-editor-form';
    const grid=document.createElement('div');grid.className='welcome-editor-grid';
    const meta=[
      {key:'verein',label:'Verein',placeholder:'z. B. Tennisclub Musterstadt',type:'text',wide:true},
      {key:'ort',label:'Ort',placeholder:'z. B. Clubhaus / Musterstadt',type:'text',wide:true},
      {key:'datum',label:'Datum',placeholder:'',type:'date'},
      {key:'uhrzeit',label:'Uhrzeit',placeholder:'',type:'time'}
    ];
    const controls={};
    for(const item of meta){
      const wrap=document.createElement('div');wrap.className='welcome-editor-field'+(item.wide?' wide':'');
      const label=document.createElement('label');
      const id='welcome-editor-'+item.key;label.htmlFor=id;label.textContent=item.label;
      const input=document.createElement('input');
      input.type=item.type;input.id=id;input.name=item.key;
      input.maxLength=limit[item.key];input.autocomplete='off';
      if(item.placeholder)input.placeholder=item.placeholder;
      wrap.append(label,input);grid.append(wrap);controls[item.key]=input;
    }
    form.append(grid);
    const note=document.createElement('p');note.className='welcome-editor-hint';
    note.textContent='Wird auf diesem Gerät gespeichert. Bei aktiver Live-Teilnahme sehen alle verbundenen Geräte die aktualisierten Angaben.';
    form.append(note);
    const actions=document.createElement('div');actions.className='welcome-editor-actions';
    const cancel=document.createElement('button');cancel.type='button';cancel.textContent='Abbrechen';
    const reset=document.createElement('button');reset.type='button';reset.textContent='Felder leeren';
    const save=document.createElement('button');save.type='submit';save.className='primary';save.textContent='Speichern und anzeigen';
    actions.append(cancel,reset,save);form.append(actions);dialog.append(form);overlay.append(dialog);
    document.body.append(overlay);
    const open=()=>{
      for(const key of fields)controls[key].value=welcome[key];
      overlay.hidden=false;controls.verein.focus();
    };
    const close=()=>{overlay.hidden=true;btn.focus()};
    btn.addEventListener('click',open);
    cancel.addEventListener('click',close);
    overlay.addEventListener('click',e=>{if(e.target===overlay)close()});
    overlay.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();e.stopPropagation();close()}});
    const apply=next=>{
      welcome=normalize(next);
      try{localStorage.setItem(KEY,JSON.stringify(welcome))}catch(e){}
      if(typeof onSaved==='function')onSaved(copy());
      close();
    };
    reset.addEventListener('click',()=>apply({}));
    form.addEventListener('submit',e=>{
      e.preventDefault();
      const data={};
      for(const key of fields)data[key]=controls[key].value;
      apply(data);
    });
    return btn;
  };
  window.TSWorkshopWelcome={get:copy,install,normalize};
})();

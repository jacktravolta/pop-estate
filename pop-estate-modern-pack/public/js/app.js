
const toast=(msg,type='success')=>{const c=document.getElementById('toastContainer');const el=document.createElement('div');el.className=`toast align-items-center text-bg-${type} border-0 show`;el.innerHTML=`<div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;c.appendChild(el);setTimeout(()=>el.remove(),4000);};
document.getElementById('sidebarOpen')?.addEventListener('click',()=>document.getElementById('sidebar').classList.add('open'));
document.getElementById('sidebarClose')?.addEventListener('click',()=>document.getElementById('sidebar').classList.remove('open'));
let debounceTimer;
document.getElementById('globalSearch')?.addEventListener('input',e=>{
  clearTimeout(debounceTimer);
  debounceTimer=setTimeout(async()=>{
    const q=e.target.value.trim(); const box=document.getElementById('globalSearchResults');
    if(q.length<2){box.style.display='none'; return;}
    box.style.display='block'; box.innerHTML='<div class="p-3 small text-muted">Buscando...</div>';
    try{
      const r=await fetch(`/api/search?q=${encodeURIComponent(q)}`,{headers:{'X-Requested-With':'XMLHttpRequest'}});
      if(r.ok){const data=await r.json(); box.innerHTML=data.items.map(i=>`<a href="${i.url}" class="d-block p-3 text-decoration-none border-bottom"><strong>${i.title}</strong><br><small class="text-muted">${i.subtitle}</small></a>`).join('')||'<div class="p-3 text-muted">Sin resultados</div>';}
      else{box.innerHTML='<div class="p-3 text-muted">Escribe para buscar empresas, propiedades...</div>';}
    }catch{box.innerHTML='<div class="p-3 text-muted">Escribe para buscar...</div>';}
  },300);
});

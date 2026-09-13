
let chatOpen=false;
const fab=document.getElementById('chatFab'), panel=document.getElementById('chatPanel'), msgs=document.getElementById('chatMessages'), input=document.getElementById('chatInput');
fab?.addEventListener('click',()=>{chatOpen=!chatOpen; panel.classList.toggle('d-none',!chatOpen); if(chatOpen) input?.focus();});
document.getElementById('chatClose')?.addEventListener('click',()=>{panel.classList.add('d-none'); chatOpen=false;});
document.getElementById('chatMinimize')?.addEventListener('click',()=>{panel.style.height=panel.style.height==='56px'?'520px':'56px';});
// Draggable header
let isDrag=false, offX, offY;
document.getElementById('chatHeader')?.addEventListener('mousedown',e=>{isDrag=true; offX=e.clientX-panel.offsetLeft; offY=e.clientY-panel.offsetTop; panel.style.transition='none';});
document.addEventListener('mousemove',e=>{if(!isDrag)return; panel.style.left=(e.clientX-offX)+'px'; panel.style.top=(e.clientY-offY)+'px'; panel.style.right='auto'; panel.style.bottom='auto'; panel.style.position='fixed';});
document.addEventListener('mouseup',()=>{isDrag=false; panel.style.transition='';});
function addMsg(text,who='user'){
  const d=document.createElement('div'); d.className='msg '+who; d.textContent=text; msgs.appendChild(d); msgs.scrollTop=msgs.scrollHeight;
  const hist=JSON.parse(localStorage.getItem('pop_chat')||'[]'); hist.push({text,who,at:Date.now()}); localStorage.setItem('pop_chat',JSON.stringify(hist.slice(-40)));
}
async function sendChat(){
  const q=input.value.trim(); if(!q)return; addMsg(q,'user'); input.value=''; const typing=document.createElement('div'); typing.className='msg bot'; typing.textContent='Escribiendo...'; msgs.appendChild(typing); msgs.scrollTop=msgs.scrollHeight;
  try{
    const res=await fetch('/api/chat',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:q})});
    const data=await res.json(); typing.remove(); addMsg(data.response||data.message||'Listo, ejecutado.', 'bot');
  }catch(e){ typing.remove(); addMsg('Error de conexión con /api/chat','bot');}
}
document.getElementById('chatSend')?.addEventListener('click',sendChat);
input?.addEventListener('keydown',e=>{if(e.key==='Enter')sendChat();});
document.querySelectorAll('.sugg').forEach(b=>b.addEventListener('click',()=>{input.value=b.dataset.prompt; sendChat();}));
try{(JSON.parse(localStorage.getItem('pop_chat')||'[]')).forEach(m=>{const d=document.createElement('div'); d.className='msg '+m.who; d.textContent=m.text; msgs.appendChild(d);});}catch{}

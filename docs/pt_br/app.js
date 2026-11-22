(function siteApp(){
"use strict";
const $=(id)=>document.getElementById(id);
const nodes={nav:$("category-nav"),title:$("section-title"),desc:$("section-description"),eyebrow:$("section-eyebrow"),count:$("example-count"),cards:$("cards"),state:$("state"),search:$("search"),subtype:$("subtype"),subtypeLabel:$("subtype-label"),more:$("more"),dialog:$("preview-dialog"),frame:$("large-frame"),code:$("source-code"),codeView:$("code-view"),preview:$("dialog-preview"),copyMessage:$("copy-message")};
let catalog=[],active=null,entries=[],filtered=[],visible=0,version=0,observer=null,frameObserver=null,sourceLoaded=null;
const cache=new Map();
const pathSafe=(path)=>/^(?:(?:page|quiz|survey|timeline|case|simulation|custom)\/[a-z0-9-]+|game-[a-z0-9-]+\/[a-z0-9-]+)\.html$/.test(path);
function el(tag,cls,content){const n=document.createElement(tag);if(cls)n.className=cls;if(content!==undefined)n.textContent=content;return n;}
async function readJSON(path){const r=await fetch(path,{cache:"no-cache"});if(!r.ok)throw Error("HTTP "+r.status);return r.json();}
function showState(message){nodes.state.textContent=message;}
function fitFrame(wrapper){const frame=wrapper.querySelector("iframe");if(frame)wrapper.style.setProperty("--preview-scale",String(wrapper.clientWidth/1024));}
function setupFrames(){if(frameObserver)frameObserver.disconnect();frameObserver=typeof ResizeObserver==="function"?new ResizeObserver((obs)=>obs.forEach((e)=>fitFrame(e.target))):null;if(observer)observer.disconnect();observer=typeof IntersectionObserver==="function"?new IntersectionObserver((obs)=>obs.forEach((e)=>{if(e.isIntersecting){const f=e.target.querySelector("iframe");if(f&&!f.src)f.src=f.dataset.src;observer.unobserve(e.target);}}),{rootMargin:"250px"}):null;}
function card(item){
 const article=el("article","example-card"),preview=el("div","card-preview"),frame=el("iframe"),open=el("button","preview-open"),body=el("div","card-body"),meta=el("div","card-meta"),pill=el("span","category-pill",item.subtype||active.label),time=el("span","card-time",(item.minutes||"5")+" min"),heading=el("h3","",item.title),desc=el("p","card-description",item.description),how=el("p","card-how"),footer=el("div","card-footer"),button=el("button","card-link","Abrir exemplo →"),copyPrompt=el("button","card-link prompt-link","Copiar comando");
 frame.title="Prévia de "+item.title;frame.loading="lazy";frame.setAttribute("sandbox","allow-scripts");frame.dataset.src=item.path;frame.setAttribute("aria-hidden","true");frame.tabIndex=-1;open.type="button";open.setAttribute("aria-label","Abrir "+item.title);open.addEventListener("click",()=>openPreview(item));button.type="button";button.addEventListener("click",()=>openPreview(item));copyPrompt.type="button";copyPrompt.setAttribute("aria-live","polite");copyPrompt.addEventListener("click",()=>copyPromptFromCard(item,copyPrompt));
 how.append(el("strong","","Como funciona: "),document.createTextNode(item.howItWorks||"Explore a experiência e suas interações."));
 preview.append(frame,open);meta.append(pill,time);footer.append(button,copyPrompt);body.append(meta,heading,desc,how,footer);article.append(preview,body);
 if(frameObserver)frameObserver.observe(preview);else requestAnimationFrame(()=>fitFrame(preview));
 if(observer)observer.observe(preview);else frame.src=item.path;
 return article;
}
function render(reset=true){if(reset){visible=0;nodes.cards.replaceChildren();setupFrames();}
 const phrase=nodes.search.value.trim().toLocaleLowerCase("pt-BR");
 filtered=entries.filter(i=>(!nodes.subtype.value||i.subtype===nodes.subtype.value)&&(!phrase||[i.title,i.description,i.howItWorks,i.subtype,...(i.features||[])].join(" ").toLocaleLowerCase("pt-BR").includes(phrase)));
 const next=Math.min(filtered.length,visible+12);const frag=document.createDocumentFragment();for(let i=visible;i<next;i++)frag.appendChild(card(filtered[i]));nodes.cards.appendChild(frag);visible=next;nodes.more.hidden=visible>=filtered.length;nodes.count.textContent=filtered.length+" exemplo"+(filtered.length===1?"":"s");showState(filtered.length?"": "Nenhum exemplo encontrado. Experimente outra busca.");}
function hashCategory(){const id=decodeURIComponent(location.hash.replace(/^#/,""));return catalog.find(c=>c.id===id)||catalog[0];}
async function selectCategory(c){if(!c)return;active=c;const request=++version;nodes.search.value="";nodes.subtype.value="";nodes.subtypeLabel.hidden=c.id!=="games";nodes.title.textContent=c.label;nodes.desc.textContent=c.description;nodes.eyebrow.textContent="EXPLORAR / "+c.label.toLocaleUpperCase("pt-BR");nodes.count.textContent="Carregando…";nodes.cards.replaceChildren();nodes.more.hidden=true;showState("Carregando exemplos…");for(const btn of nodes.nav.querySelectorAll("button"))btn.setAttribute("aria-current",String(btn.dataset.category===c.id));
 try{if(!cache.has(c.id)){const data=await readJSON(c.manifest);if(!data||!Array.isArray(data.examples))throw Error("Manifesto inválido");cache.set(c.id,data);}if(version!==request)return;entries=cache.get(c.id).examples.filter(i=>i&&typeof i.title==="string"&&pathSafe(i.path)&&(c.id==="games"?/^game-[a-z0-9-]+\/[a-z0-9-]+\.html$/.test(i.path):i.path.startsWith(c.id+"/")));nodes.subtype.replaceChildren(new Option("Todos os jogos",""));if(c.id==="games"){const types=[...new Set(entries.map(i=>i.subtype).filter(Boolean))].sort((a,b)=>a.localeCompare(b,"pt-BR"));for(const s of types)nodes.subtype.add(new Option(s,s));}render();}catch(error){if(version!==request)return;entries=[];nodes.count.textContent="Indisponível";showState("Não foi possível carregar esta categoria. Confira o manifesto JSON ou tente recarregar a página.");console.error(error);}
}
let selectedPrompt="";
function showDialogView(mode){
 nodes.preview.hidden=mode!=="preview";
 nodes.codeView.hidden=mode!=="html";
 $("prompt-view").hidden=mode!=="prompt";
 $("toggle-code").textContent=mode==="html"?"Ver prévia":"Ver HTML";
 $("toggle-code").setAttribute("aria-expanded",String(mode==="html"));
 $("toggle-prompt").textContent=mode==="prompt"?"Ver prévia":"Ver comando";
 $("toggle-prompt").setAttribute("aria-expanded",String(mode==="prompt"));
}
async function openPreview(item){
 nodes.dialog.querySelector("#dialog-title").textContent=item.title;
 $("dialog-description").textContent=item.description;
 $("open-tab").href=item.path;
 nodes.frame.src=item.path;
 selectedPrompt=typeof item.prompt==="string"?item.prompt:"";
 $("prompt-text").textContent=selectedPrompt;
 $("toggle-prompt").hidden=!selectedPrompt;
 $("copy-prompt").hidden=!selectedPrompt;
 showDialogView("preview");
 nodes.copyMessage.textContent="";
 sourceLoaded=null;
 if(typeof nodes.dialog.showModal==="function")nodes.dialog.showModal();
 else nodes.dialog.setAttribute("open","");
}
async function loadSource(){
 if(!sourceLoaded){
  sourceLoaded=fetch($("open-tab").href).then(r=>{
   if(!r.ok)throw Error("HTTP "+r.status);
   return r.text();
  }).catch(e=>{sourceLoaded=null;throw e;});
 }
 return sourceLoaded;
}
async function copyText(text){
 if(!text)throw Error("Comando não encontrado");
 if(navigator.clipboard&&typeof navigator.clipboard.writeText==="function"){
  try{await navigator.clipboard.writeText(text);return;}catch(_){/* Restricted context: use fallback. */}
 }
 const area=el("textarea");
 area.value=text;
 area.setAttribute("aria-hidden","true");
 area.style.position="fixed";
 area.style.left="-9999px";
 document.body.append(area);
 try{area.select();if(!document.execCommand("copy"))throw Error("Cópia não permitida");}
 finally{area.remove();}
}
async function copyPromptFromCard(item,button){
 try{
  await copyText(item.prompt);
  button.textContent="Comando copiado!";
  button.setAttribute("aria-label","Comando do exemplo "+item.title+" copiado");
 }catch(_){
  await openPreview(item);
  showDialogView("prompt");
  nodes.copyMessage.textContent="Não foi possível copiar automaticamente; selecione o comando.";
 }
}
$("toggle-code").addEventListener("click",async()=>{
 if(!nodes.codeView.hidden){showDialogView("preview");return;}
 try{nodes.code.textContent=await loadSource();showDialogView("html");}
 catch(_){nodes.copyMessage.textContent="HTML indisponível.";}
});
$("toggle-prompt").addEventListener("click",()=>showDialogView($("prompt-view").hidden?"prompt":"preview"));
$("copy-code").addEventListener("click",async()=>{
 try{await copyText(await loadSource());nodes.copyMessage.textContent="HTML copiado!";}
 catch(_){nodes.copyMessage.textContent="Não foi possível copiar. Use Ver HTML e selecione o código.";}
});
$("copy-prompt").addEventListener("click",async()=>{
 try{await copyText(selectedPrompt);nodes.copyMessage.textContent="Comando copiado!";}
 catch(_){nodes.copyMessage.textContent="Não foi possível copiar. Use Ver comando e selecione o texto.";}
});
$("close-dialog").addEventListener("click",()=>nodes.dialog.close?nodes.dialog.close():nodes.dialog.removeAttribute("open"));
nodes.dialog.addEventListener("close",()=>{nodes.frame.removeAttribute("src");nodes.copyMessage.textContent="";});
nodes.dialog.addEventListener("click",e=>{if(e.target===nodes.dialog&&nodes.dialog.close)nodes.dialog.close();});
nodes.search.addEventListener("input",()=>render());nodes.subtype.addEventListener("change",()=>render());nodes.more.addEventListener("click",()=>render(false));
window.addEventListener("hashchange",()=>selectCategory(hashCategory()));
window.addEventListener("resize",()=>{if(!frameObserver)document.querySelectorAll(".card-preview").forEach(fitFrame);});
(async()=>{try{const data=await readJSON("./catalog.json");catalog=(data.categories||[]).filter(c=>/^[a-z-]+$/.test(c.id)&&c.manifest===c.id+"/examples.json");for(const c of catalog){const b=el("button","nav-button",c.label);b.type="button";b.dataset.category=c.id;b.setAttribute("aria-current","false");b.addEventListener("click",()=>{if(location.hash==="#"+c.id)selectCategory(c);else location.hash=c.id;});nodes.nav.appendChild(b);}if(catalog.length)await selectCategory(hashCategory());else showState("Catálogo sem categorias.");}catch(e){showState("Não foi possível carregar o catálogo. Abra este site por HTTP(S), e não como arquivo local.");nodes.count.textContent="Indisponível";console.error(e);}})();
})();

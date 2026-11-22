(function siteApp(){
"use strict";
const $=(id)=>document.getElementById(id);
const nodes={nav:$("category-nav"),title:$("section-title"),desc:$("section-description"),eyebrow:$("section-eyebrow"),count:$("example-count"),cards:$("cards"),state:$("state"),search:$("search"),subtype:$("subtype"),subtypeLabel:$("subtype-label"),pagination:$("pagination"),dialog:$("preview-dialog"),frame:$("large-frame"),code:$("source-code"),codeView:$("code-view"),preview:$("dialog-preview"),copyMessage:$("copy-message")};
let catalog=[],active=null,entries=[],entriesCategory=null,filtered=[],currentPage=1,version=0,observer=null,frameObserver=null,sourceLoaded=null,openedPreviewPath=null;
const PAGE_SIZE=9,PAGINATE_FROM=20;
const cache=new Map();
const pathSafe=(path)=>/^(?:(?:page|quiz|survey|timeline|case|simulation|custom)\/[a-z0-9-]+|game-[a-z0-9-]+\/[a-z0-9-]+)\.html$/.test(path);
function el(tag,cls,content){const n=document.createElement(tag);if(cls)n.className=cls;if(content!==undefined)n.textContent=content;return n;}
async function readJSON(path){const r=await fetch(path,{cache:"no-cache"});if(!r.ok)throw Error("HTTP "+r.status);return r.json();}
function showState(message){nodes.state.textContent=message;}
function fitFrame(wrapper){const frame=wrapper.querySelector("iframe");if(frame)wrapper.style.setProperty("--preview-scale",String(wrapper.clientWidth/1024));}
function setupFrames(){if(frameObserver)frameObserver.disconnect();frameObserver=typeof ResizeObserver==="function"?new ResizeObserver((obs)=>obs.forEach((e)=>fitFrame(e.target))):null;if(observer)observer.disconnect();observer=typeof IntersectionObserver==="function"?new IntersectionObserver((obs)=>obs.forEach((e)=>{if(e.isIntersecting){const f=e.target.querySelector("iframe");if(f&&!f.src)f.src=f.dataset.src;observer.unobserve(e.target);}}),{rootMargin:"250px"}):null;}
function card(item){
 const article=el("article","example-card"),preview=el("div","card-preview"),frame=el("iframe"),open=el("button","preview-open"),body=el("div","card-body"),meta=el("div","card-meta"),pill=el("span","category-pill",item.subtype||active.label),time=el("span","card-time",(item.minutes||"5")+" min"),heading=el("h3","",item.title),desc=el("p","card-description",item.description),how=el("p","card-how"),footer=el("div","card-footer"),button=el("button","card-link","Abrir exemplo →");
 frame.title="Prévia de "+item.title;frame.loading="lazy";frame.setAttribute("sandbox","allow-scripts");frame.dataset.src=item.path;frame.setAttribute("aria-hidden","true");frame.tabIndex=-1;open.type="button";open.setAttribute("aria-label","Abrir "+item.title);open.addEventListener("click",()=>openPreview(item));button.type="button";button.addEventListener("click",()=>openPreview(item));
 how.append(el("strong","","Como funciona: "),document.createTextNode(item.howItWorks||"Explore a experiência e suas interações."));
 preview.append(frame,open);meta.append(pill,time);footer.append(button);body.append(meta,heading,desc,how,footer);article.append(preview,body);
 if(frameObserver)frameObserver.observe(preview);else requestAnimationFrame(()=>fitFrame(preview));
 if(observer)observer.observe(preview);else frame.src=item.path;
 return article;
}
function pageFromURL(){
 const raw=new URL(location.href).searchParams.get("page");
 if(!raw||!/^[1-9]\d*$/.test(raw))return 1;
 const page=Number(raw);
 return Number.isSafeInteger(page)?page:1;
}
function writePageURL(page,replace=false){
 const url=new URL(location.href);
 if(active)url.searchParams.set("category",active.id);
 if(page===1)url.searchParams.delete("page");else url.searchParams.set("page",String(page));
 if(catalog.some(c=>url.hash==="#"+c.id))url.hash="";
 if(url.href!==location.href)history[replace?"replaceState":"pushState"](null,"",url);
}
function navigatePage(page){
 if(page===currentPage||page<1)return;
 currentPage=page;
 writePageURL(page);
 render();
 const selected=nodes.pagination.querySelector('[aria-current="page"]');
 if(selected)selected.focus();
 $("examples").scrollIntoView({block:"start",behavior:"auto"});
}
function paginationButton(label,page,description,disabled=false,current=false){
 const button=el("button","pagination-button",label);
 button.type="button";button.disabled=disabled;button.dataset.page=String(page);
 button.setAttribute("aria-label",description);
 if(current)button.setAttribute("aria-current","page");
 button.addEventListener("click",()=>navigatePage(page));
 return button;
}
function renderPagination(totalPages){
 nodes.pagination.replaceChildren();
 nodes.pagination.hidden=totalPages<=1;
 if(totalPages<=1)return;
 nodes.pagination.appendChild(paginationButton("«",currentPage-1,"Página anterior",currentPage===1));
 const pages=[];
 if(totalPages<=7){for(let p=1;p<=totalPages;p++)pages.push(p);}
 else{
  const first=Math.max(2,Math.min(currentPage-1,totalPages-4));
  const last=Math.min(totalPages-1,first+3);
  pages.push(1);
  for(let p=first;p<=last;p++)pages.push(p);
  pages.push(totalPages);
 }
 let previous=0;
 for(const page of pages){
  if(page-previous>1)nodes.pagination.appendChild(el("span","pagination-ellipsis","…"));
  nodes.pagination.appendChild(paginationButton(String(page),page,"Página "+page,false,page===currentPage));
  previous=page;
 }
 nodes.pagination.appendChild(paginationButton("»",currentPage+1,"Próxima página",currentPage===totalPages));
}
function render(){
 nodes.cards.replaceChildren();setupFrames();
 const phrase=nodes.search.value.trim().toLocaleLowerCase("pt-BR");
 filtered=entries.filter(i=>(!nodes.subtype.value||i.subtype===nodes.subtype.value)&&(!phrase||[i.title,i.description,i.howItWorks,i.subtype,...(i.features||[])].join(" ").toLocaleLowerCase("pt-BR").includes(phrase)));
 const paginated=filtered.length>=PAGINATE_FROM;
 const totalPages=paginated?Math.ceil(filtered.length/PAGE_SIZE):1;
 const requestedPage=currentPage;
 currentPage=Math.max(1,Math.min(currentPage,totalPages));
 const urlPage=new URL(location.href).searchParams.get("page");
 if(currentPage!==requestedPage||(urlPage!==null&&(currentPage===1||urlPage!==String(currentPage))))writePageURL(currentPage,true);
 const first=paginated?(currentPage-1)*PAGE_SIZE:0;
 const last=paginated?Math.min(first+PAGE_SIZE,filtered.length):filtered.length;
 const fragment=document.createDocumentFragment();
 for(let i=first;i<last;i++)fragment.appendChild(card(filtered[i]));
 nodes.cards.appendChild(fragment);
 renderPagination(totalPages);
 nodes.count.textContent=filtered.length+" exemplo"+(filtered.length===1?"":"s")+(paginated?" · página "+currentPage+" de "+totalPages:"");
 showState(filtered.length?"":"Nenhum exemplo encontrado. Experimente outra busca.");
}
function categoryFromURL(){
 const url=new URL(location.href);
 const requested=url.searchParams.get("category");
 const legacy=catalog.find(c=>url.hash==="#"+c.id);
 const category=catalog.find(c=>c.id===requested)||legacy||catalog[0];
 if(category&&(legacy||(requested!==null&&requested!==category.id))){
  url.searchParams.set("category",category.id);
  if(legacy)url.hash="";
  if(url.href!==location.href)history.replaceState(null,"",url);
 }
 return category;
}
function renderHero(category){
 const hero=document.querySelector("section.hero.container");
 const config=category.hero;
 if(!hero||!config)return;
 const eyebrow=hero.querySelector(".hero-copy .eyebrow");
 const dot=el("span","eyebrow-dot");
 dot.setAttribute("aria-hidden","true");
 eyebrow.replaceChildren(dot,document.createTextNode(" "+config.eyebrow));
 hero.querySelector("#hero-title").replaceChildren(document.createTextNode(config.title+" "),el("em","",config.emphasis));
 hero.querySelector(".hero-description").textContent=config.description;
 hero.querySelector(".hero-pills").replaceChildren(...config.pills.map(text=>el("span","",text)));
 hero.querySelector(".art-kicker").textContent=config.artKicker;
 hero.querySelector(".art-top i").textContent="mod_mcp / "+category.id;
}
async function selectCategory(c){if(!c)return;closePreview(false);active=c;renderHero(c);entriesCategory=null;entries=[];currentPage=pageFromURL();const request=++version;nodes.search.value="";nodes.subtype.value="";nodes.subtypeLabel.hidden=c.id!=="games";nodes.title.textContent=c.label;nodes.desc.textContent=c.description;nodes.eyebrow.textContent="EXPLORAR / "+c.label.toLocaleUpperCase("pt-BR");nodes.count.textContent="Carregando…";nodes.cards.replaceChildren();nodes.pagination.hidden=true;nodes.pagination.replaceChildren();showState("Carregando exemplos…");for(const btn of nodes.nav.querySelectorAll("button"))btn.setAttribute("aria-current",String(btn.dataset.category===c.id));
 try{if(!cache.has(c.id)){const data=await readJSON(c.manifest);if(!data||!Array.isArray(data.examples))throw Error("Manifesto inválido");cache.set(c.id,data);}if(version!==request)return;entries=cache.get(c.id).examples.filter(i=>i&&typeof i.title==="string"&&pathSafe(i.path)&&(c.id==="games"?/^game-[a-z0-9-]+\/[a-z0-9-]+\.html$/.test(i.path):i.path.startsWith(c.id+"/")));nodes.subtype.replaceChildren(new Option("Todos os jogos",""));if(c.id==="games"){const types=[...new Set(entries.map(i=>i.subtype).filter(Boolean))].sort((a,b)=>a.localeCompare(b,"pt-BR"));for(const s of types)nodes.subtype.add(new Option(s,s));}entriesCategory=c.id;currentPage=pageFromURL();render();syncPreviewFromURL();}catch(error){if(version!==request)return;entries=[];nodes.count.textContent="Indisponível";showState("Não foi possível carregar esta categoria. Confira o manifesto JSON ou tente recarregar a página.");console.error(error);}
}
let selectedPrompt="";
function showDialogView(mode){
 nodes.preview.hidden=mode!=="preview";
 nodes.codeView.hidden=mode!=="html";
 $("prompt-view").hidden=mode!=="prompt";
 for(const tab of $("dialog-tabs").querySelectorAll('[role="tab"]')){
  const selected=tab.dataset.view===mode;
  tab.setAttribute("aria-selected",String(selected));
  tab.tabIndex=selected?0:-1;
 }
 $("copy-code").hidden=mode!=="html";
 nodes.copyMessage.textContent="";
}
async function activateDialogTab(mode){
 if(mode==="prompt"&&!selectedPrompt)return;
 showDialogView(mode);
 if(mode!=="html")return;
 const requestedPath=openedPreviewPath;
 nodes.code.textContent="Carregando HTML…";
 try{
  const html=await loadSource();
  if(openedPreviewPath===requestedPath)nodes.code.textContent=html;
 }catch(_){
  if(openedPreviewPath===requestedPath){
   nodes.code.textContent="HTML indisponível.";
   if(!nodes.codeView.hidden)nodes.copyMessage.textContent="Não foi possível carregar o HTML.";
  }
 }
}
function closePreview(updateURL=true){
 const closingPath=openedPreviewPath;
 openedPreviewPath=null;
 if(nodes.dialog.open){
  if(typeof nodes.dialog.close==="function")nodes.dialog.close();
  else nodes.dialog.removeAttribute("open");
 }
 nodes.frame.removeAttribute("src");
 nodes.copyMessage.textContent="";
 if(updateURL&&closingPath){
  const url=new URL(location.href);
  if(url.searchParams.get("preview")===closingPath){
   url.searchParams.delete("preview");
   history.pushState(null,"",url);
  }
 }
}
function openPreview(item,updateURL=true){
 if(updateURL){
  const url=new URL(location.href);
  url.searchParams.set("category",active.id);
  url.searchParams.set("preview",item.path);
  if(catalog.some(c=>url.hash==="#"+c.id))url.hash="";
  if(url.href!==location.href)history.pushState(null,"",url);
 }
 openedPreviewPath=item.path;
 nodes.dialog.querySelector("#dialog-title").textContent=item.title;
 $("dialog-description").textContent=item.description;
 $("open-tab").href=item.path;
 nodes.frame.src=item.path;
 selectedPrompt=typeof item.prompt==="string"?item.prompt:"";
 $("prompt-text").textContent=selectedPrompt;
 $("tab-prompt").hidden=!selectedPrompt;
 showDialogView("preview");
 nodes.copyMessage.textContent="";
 sourceLoaded=null;
 if(!nodes.dialog.open){
  if(typeof nodes.dialog.showModal==="function")nodes.dialog.showModal();
  else nodes.dialog.setAttribute("open","");
 }
}
function syncPreviewFromURL(){
 const url=new URL(location.href);
 const requested=url.searchParams.get("preview");
 if(requested===null){
  if(nodes.dialog.open)closePreview(false);
  return;
 }
 const item=entries.find(entry=>entry.path===requested);
 if(!item){
  url.searchParams.delete("preview");
  history.replaceState(null,"",url);
  if(nodes.dialog.open)closePreview(false);
  return;
 }
 if(!nodes.dialog.open||openedPreviewPath!==item.path)openPreview(item,false);
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
$("dialog-tabs").addEventListener("click",(event)=>{
 const tab=event.target.closest('[role="tab"]');
 if(tab&&!tab.hidden)activateDialogTab(tab.dataset.view);
});
$("dialog-tabs").addEventListener("keydown",(event)=>{
 if(!["ArrowRight","ArrowLeft","Home","End"].includes(event.key))return;
 const tabs=[...$("dialog-tabs").querySelectorAll('[role="tab"]:not([hidden])')];
 const index=tabs.indexOf(document.activeElement);
 if(index===-1)return;
 event.preventDefault();
 let next=index;
 if(event.key==="ArrowRight")next=(index+1)%tabs.length;
 if(event.key==="ArrowLeft")next=(index-1+tabs.length)%tabs.length;
 if(event.key==="Home")next=0;
 if(event.key==="End")next=tabs.length-1;
 tabs[next].focus();
 activateDialogTab(tabs[next].dataset.view);
});
$("copy-code").addEventListener("click",async()=>{
 try{await copyText(await loadSource());nodes.copyMessage.textContent="HTML copiado!";}
 catch(_){nodes.copyMessage.textContent="Não foi possível copiar. Use Ver HTML e selecione o código.";}
});
$("close-dialog").addEventListener("click",()=>closePreview());
nodes.dialog.addEventListener("close",()=>{if(!nodes.dialog.open)closePreview();});
nodes.dialog.addEventListener("click",e=>{if(e.target===nodes.dialog)closePreview();});
function resetPagination(){currentPage=1;writePageURL(1);render();}
nodes.search.addEventListener("input",resetPagination);
nodes.subtype.addEventListener("change",resetPagination);
function syncFromURL(){
 if(!catalog.length)return;
 const category=categoryFromURL();
 if(!active||active.id!==category.id)selectCategory(category);
 else if(entriesCategory===category.id){
  if(currentPage!==pageFromURL()){currentPage=pageFromURL();render();}
  syncPreviewFromURL();
 }
}
window.addEventListener("popstate",syncFromURL);
window.addEventListener("hashchange",syncFromURL);
window.addEventListener("resize",()=>{if(!frameObserver)document.querySelectorAll(".card-preview").forEach(fitFrame);});
(async()=>{try{const data=await readJSON("./catalog.json");catalog=(data.categories||[]).filter(c=>/^[a-z-]+$/.test(c.id)&&c.manifest===c.id+"/examples.json");for(const c of catalog){const b=el("button","nav-button",c.label);b.type="button";b.dataset.category=c.id;b.setAttribute("aria-current","false");b.addEventListener("click",()=>{
 const url=new URL(location.href);
 url.searchParams.set("category",c.id);url.searchParams.delete("page");url.searchParams.delete("preview");url.hash="";
 if(url.href!==location.href)history.pushState(null,"",url);
 selectCategory(c);
});nodes.nav.appendChild(b);}if(catalog.length)await selectCategory(categoryFromURL());else showState("Catálogo sem categorias.");}catch(e){showState("Não foi possível carregar o catálogo. Abra este site por HTTP(S), e não como arquivo local.");nodes.count.textContent="Indisponível";console.error(e);}})();
})();

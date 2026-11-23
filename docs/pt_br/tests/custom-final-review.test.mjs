/**
 * Revisão transversal dos quinze exemplos educativos de custom/.
 * Executar: node --test docs/pt_br/tests/custom-final-review.test.mjs
 * Estas verificações estáticas complementam os testes funcionais individuais.
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const examples=[
 ["flashcards-de-revisao","flashcards"],
 ["mapa-mental-exploravel","concept-map"],
 ["roteiro-de-estudo-personalizado","study-route"],
 ["votacao-rapida","quick-poll"],
 ["comparador-de-tecnicas","technique-comparator"],
 ["mapa-conceitual-interativo","concept-map-builder"],
 ["quadro-de-organizacao-de-ideias","ideas-board"],
 ["construtor-de-fluxogramas","flowchart-builder"],
 ["matriz-de-decisao-interativa","decision-matrix"],
 ["explorador-de-imagens-com-pontos-interativos","image-hotspots"],
 ["planejador-de-aprendizagem","learning-planner"],
 ["linha-do-tempo-investigativa","investigative-timeline"],
 ["simulador-de-cenarios-e-consequencias","scenario-simulator"],
 ["oficina-de-argumentos-e-evidencias","argument-workshop"],
 ["laboratorio-de-diagnostico-e-correcao","diagnostic-lab"]
];
const getCatalog=async()=>JSON.parse(await read("custom/examples.json"));
test("quinze exemplos em ordem, com identificadores e caminhos únicos",async()=>{
 const m=await getCatalog();
 assert.equal(m.version,1);
 assert.equal(m.category,"custom");
 assert.ok(m.examples.length>=15,"A galeria precisa conter todos os 15 exemplos.");
 assert.deepEqual(m.examples.slice(0,15).map(x=>x.id),examples.map(x=>x[0]));
 const ids=new Set(),paths=new Set();
 for(const entry of m.examples){
  assert.match(entry.id,/^[a-z0-9-]+$/);
  assert.ok(!ids.has(entry.id),"Identificador duplicado: "+entry.id);
  ids.add(entry.id);
  assert.equal(entry.path,"custom/"+entry.id+".html");
  assert.ok(!paths.has(entry.path),"Caminho duplicado: "+entry.path);
  paths.add(entry.path);
  assert.ok(entry.title.length>=5&&entry.description.length>=20);
  assert.ok(entry.howItWorks.length>=40);
  assert.ok(Number.isInteger(entry.minutes)&&entry.minutes>0);
  assert.ok(Array.isArray(entry.features)&&entry.features.length>=2);
 }
});
test("quinze prompts perguntam primeiro ao professor e aguardam a resposta",async()=>{
 const m=await getCatalog();
 for(const item of m.examples.slice(0,15)){
  const prompt=item.prompt;
  assert.ok(typeof prompt==="string"&&prompt.length>350,"Prompt de "+item.id);
  const first=prompt.indexOf("Na sua PRIMEIRA resposta");
  const question=prompt.indexOf("Qual tipo de conteúdo você deseja trabalhar");
  const wait=prompt.indexOf("Aguarde a resposta");
  assert.ok(first>=0&&first<question&&question<wait,
    "A pergunta deve preceder a instrução de aguardar: "+item.id);
  assert.ok(question<1200,"Pergunta fora das primeiras instruções: "+item.id);
  assert.match(prompt,/antes de (criar|produzir|gerar|montar|escrever|desenvolver)/i,
    "Não gerar o conteúdo antes da pergunta: "+item.id);
 }
});
test("quinze páginas independentes com scripts válidos, acessibilidade e DOM seguro",async()=>{
 for(const [id] of examples){
  const path="custom/"+id+".html",html=await read(path);
  assert.match(html,/^<!doctype html>/i,path);
  assert.match(html,/<html lang="pt-BR">/,path);
  assert.match(html,/<meta name="viewport"/,path);
  assert.match(html,/<style>[\s\S]+<\/style>/,path);
  assert.match(html,/<button\b/,path);
  assert.match(html,/:focus-visible/,path);
  assert.match(html,/@media\s*\(\s*max-width:/,path);
  assert.match(html,/prefers-reduced-motion\s*:\s*reduce/,path);
  assert.match(html,/aria-live=/,path);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<iframe\b|@import\s+|<img[^>]+src=["']https?:/i,path);
  const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
  assert.equal(scripts.length,1,"Exatamente um script local: "+path);
  const script=scripts[0][1];
  assert.doesNotThrow(()=>new Script(script,{filename:path}),path);
  assert.doesNotMatch(script,/\binnerHTML\s*=|\bouterHTML\s*=|document\.write\s*\(|\beval\s*\(|\bnew Function\s*\(|\bfetch\s*\(|\bXMLHttpRequest\b|\blocalStorage\b|\bsessionStorage\b/,path);
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(x=>x[1]);
  assert.equal(new Set(ids).size,ids.length,"ID HTML duplicado: "+path);
  const present=new Set(ids);
  for(const ref of script.matchAll(/\$\("([^"]+)"\)/g))
   assert.ok(present.has(ref[1]),"Elemento não encontrado: "+ref[1]+" em "+path);
 }
});
test("os quinze exemplos possuem suítes independentes de verificação",async()=>{
 for(const [id,name] of examples){
  const path="tests/"+name+".test.mjs",source=await read(path);
  assert.match(source,/import test from ["']node:test["']/,id);
  assert.match(source,/\btest\(["']/,id);
  assert.ok(source.includes("custom/examples.json"),id);
  assert.ok(source.includes(id),id);
  assert.doesNotThrow(()=>new Script(source
    .replace(/^import .*;\s*$/gm,"")
    .replace(/^const root\s*=.*;\s*$/gm,"")
    .replace(/^const read\s*=.*;\s*$/gm,""),{filename:path}),id);
 }
});

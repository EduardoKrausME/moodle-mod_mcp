/**
 * Regressões do jogo 13 - Complete as lacunas.
 * Execute: node --test docs/pt_br/tests/fill-blanks.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-complete-as-lacunas/ciclo-da-agua.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O jogo precisa de JavaScript incorporado');
const start=js.indexOf('const DATA='),pure=js.indexOf('function normalize(value,settings)'),end=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pure>start&&end>pure,'Não foi possível localizar as regras puras');
const source=js.slice(start,js.indexOf('const root=',start))+js.slice(pure,end);
const {DATA,normalize,getBlank,validate,initialState,isCorrect,correctCount,validState,transition}=
 new Function(source+'return {DATA,normalize,getBlank,validate,initialState,isCorrect,correctCount,validState,transition};')();
const act=(state,type,more={})=>transition(state,{type,...more},DATA);

test('HTML isolado, acessível, responsivo, com CSS e um script local',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.match(html,/<script>[\s\S]+<\/script>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/@media\(max-width:510px\)/);
 assert.doesNotThrow(()=>new Script(js));
});

test('dados possuem onze lacunas, segmentos estruturados e banco com opções extras',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'fill_blanks');
 assert.equal(DATA.blanks.length,11);
 assert.equal(DATA.settings.mode,'select');
 assert.equal(DATA.settings.allowTyping,true);
 assert.equal(DATA.settings.wordBank.length,14);
 assert.ok(DATA.segments.some(part=>part.text&&part.text.includes('chuva')));
 assert.ok(DATA.segments.some(part=>part.text&&part.text.includes('.')));
});

test('duas lacunas diferentes aceitam a mesma palavra sem compartilhar estado',()=>{
 const a=getBlank(DATA,'evaporacao-a'),b=getBlank(DATA,'evaporacao-b');
 assert.ok(a&&b);
 assert.deepEqual(a.answers,b.answers);
 let s=initialState(DATA);
 s=act(s,'set',{id:a.id,value:'evaporação',source:'select'}).state;
 assert.equal(s.answers[a.id],'evaporação');
 assert.equal(s.answers[b.id],undefined);
 s=act(s,'set',{id:b.id,value:'evaporação',source:'select'}).state;
 assert.equal(isCorrect(DATA,a.id,s.answers[a.id]),true);
 assert.equal(isCorrect(DATA,b.id,s.answers[b.id]),true);
});

test('IDs repetidos nos segmentos, lacunas desconhecidas e lacuna não referenciada são rejeitados',()=>{
 const duplicate=structuredClone(DATA);
 duplicate.segments.push({blankId:'evaporacao-a'});
 assert.equal(validate(duplicate),false);
 const unknown=structuredClone(DATA);
 unknown.segments[1].blankId='inexistente';
 assert.equal(validate(unknown),false);
 const missing=structuredClone(DATA);
 missing.segments=missing.segments.filter(part=>part.blankId!=='evaporacao-b');
 assert.equal(validate(missing),false);
 const repeatedId=structuredClone(DATA);
 repeatedId.blanks[1].id=repeatedId.blanks[0].id;
 assert.equal(validate(repeatedId),false);
});

test('banco sem palavra correspondente, opções repetidas e resposta vazia falham',()=>{
 const missing=structuredClone(DATA);
 missing.settings.wordBank=missing.settings.wordBank.filter(x=>x!=='evaporação');
 assert.equal(validate(missing),false);
 const duplicate=structuredClone(DATA);
 duplicate.settings.wordBank.push('EVAPORACAO');
 assert.equal(validate(duplicate),false);
 const empty=structuredClone(DATA);
 empty.blanks[0].answers=[''];
 assert.equal(validate(empty),false);
});

test('normalização remove somente variações configuradas e aceita sinônimos explicitamente listados',()=>{
 assert.equal(normalize('  VAPOR   DE   ÁGUA  ',DATA.settings),'vapor de agua');
 assert.equal(isCorrect(DATA,'vapor','  VAPOR   DE   AGUA '),true);
 assert.equal(isCorrect(DATA,'vapor',"vapor d'água"),true);
 assert.equal(isCorrect(DATA,'ciclo','ciclo hidrológico'),true);
 assert.equal(isCorrect(DATA,'escoamento','escoamento'),true);
 assert.equal(isCorrect(DATA,'condensacao','precipitação'),false);
 const strict={...DATA.settings,ignoreAccents:false};
 assert.notEqual(normalize('condensação',strict),normalize('condensacao',strict));
});

test('seleção rejeita palavras fora do banco e permite limpar uma escolha',()=>{
 let s=initialState(DATA);
 assert.equal(act(s,'set',{id:'solo',value:'não existe',source:'select'}).changed,false);
 assert.equal(act(s,'set',{id:'invalida',value:'solo',source:'select'}).changed,false);
 s=act(s,'set',{id:'solo',value:'solo',source:'select'}).state;
 assert.equal(s.answers.solo,'solo');
 s=act(s,'set',{id:'solo',value:'',source:'select'}).state;
 assert.equal(s.answers.solo,undefined);
 assert.equal(validState(s,DATA),true);
});

test('digitação preserva espaços durante a entrada de respostas compostas',()=>{
 let s=act(initialState(DATA),'mode',{value:'type'}).state;
 s=act(s,'set',{id:'vapor',value:'vapor ',source:'type'}).state;
 assert.equal(s.answers.vapor,'vapor ');
 s=act(s,'set',{id:'vapor',value:'vapor de ',source:'type'}).state;
 assert.equal(s.answers.vapor,'vapor de ');
 s=act(s,'set',{id:'vapor',value:'vapor de agua',source:'type'}).state;
 assert.equal(isCorrect(DATA,'vapor',s.answers.vapor),true);
 s=act(s,'mode',{value:'select'}).state;
 assert.equal(s.answers.vapor,'vapor de agua');
 assert.equal(validState(s,DATA),true);
});

test('verificação individual não revela resposta errada e permite corrigir',()=>{
 let s=initialState(DATA);
 s=act(s,'set',{id:'solo',value:'erosão',source:'select'}).state;
 let out=act(s,'check',{id:'solo'});
 assert.equal(out.kind,'warn');
 assert.equal(out.state.checked.solo,'wrong');
 assert.equal(out.state.checks,1);
 assert.equal(out.message.includes('solo'),false);
 s=act(out.state,'set',{id:'solo',value:'solo',source:'select'}).state;
 assert.equal(s.checked.solo,undefined);
 out=act(s,'check',{id:'solo'});
 assert.equal(out.kind,'right');
 assert.equal(out.state.checked.solo,'right');
 assert.equal(correctCount(DATA,out.state),1);
 assert.equal(validState(out.state,DATA),true);
});

test('conferir todas as lacunas calcula progresso e reconhece conclusão somente quando correto',()=>{
 let s=initialState(DATA),first=act(s,'checkAll');
 assert.equal(first.changed,true);
 assert.equal(first.state.completed,false);
 assert.equal(correctCount(DATA,first.state),0);
 assert.equal(first.state.checks,1);
 assert.equal(validState(first.state,DATA),true);
 s=first.state;
 for(const b of DATA.blanks){
  s=act(s,'set',{id:b.id,value:b.answers[0],source:'select'}).state;
 }
 const result=act(s,'checkAll');
 assert.equal(result.state.completed,true);
 assert.equal(result.state.checks,2);
 assert.equal(correctCount(DATA,result.state),11);
 assert.equal(validState(result.state,DATA),true);
 s=act(result.state,'set',{id:'solo',value:'erosão',source:'select'}).state;
 assert.equal(s.completed,false);
 assert.equal(s.checked.solo,undefined);
});

test('dicas não preenchem a resposta e limite de quatro usos é respeitado',()=>{
 let s=initialState(DATA);
 for(const id of DATA.blanks.slice(0,4).map(b=>b.id)){
  const hint=act(s,'hint',{id});
  assert.equal(hint.changed,true);
  assert.equal(hint.state.answers[id],undefined);
  s=hint.state;
 }
 assert.equal(s.hints.length,4);
 assert.equal(act(s,'hint',{id:s.hints[0]}).changed,false);
 assert.equal(act(s,'hint',{id:DATA.blanks[5].id}).changed,false);
 assert.equal(validState(s,DATA),true);
});

test('estado adulterado com resposta, ID, status ou conclusão falsos é rejeitado',()=>{
 const base=initialState(DATA);
 const foreign=structuredClone(base);
 foreign.answers.intrusa='solo';
 assert.equal(validState(foreign,DATA),false);
 const dishonest=structuredClone(base);
 dishonest.answers.solo='erosão';dishonest.checked.solo='right';
 assert.equal(validState(dishonest,DATA),false);
 const malformed=structuredClone(base);
 malformed.answers.solo=13;
 assert.equal(validState(malformed,DATA),false);
 const repeated=structuredClone(base);
 repeated.hints=['solo','solo'];
 assert.equal(validState(repeated,DATA),false);
 const forced=structuredClone(base);
 forced.completed=true;
 assert.equal(validState(forced,DATA),false);
});

test('HTML perigoso permanece como texto e nunca é inserido com innerHTML',()=>{
 const attack=structuredClone(DATA);
 attack.segments[0].text='<img src=x onerror=alert(1)> O texto de ciências começa aqui. ';
 assert.equal(validate(attack),true,'texto pedagogicamente fornecido pode conter literais HTML');
 assert.match(js,/document\.createTextNode\(part\.text\)/);
 assert.match(js,/textContent/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
 assert.match(js,/word-bank"\)\.append\(el\("span","word",word\)\)/);
});

test('integração de progresso é opcional e não atribui nota ou conclusão oficial',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:revision\.version/);
 assert.match(js,/TextEncoder/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
});

test('manifesto local e catálogo geral possuem o mesmo registro e prompt que aguarda o professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-complete-as-lacunas/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='complete-as-lacunas-ciclo-da-agua';
 const a=local.examples.find(e=>e.id===id),b=global.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/13-complete-as-lacunas\.md/);
});

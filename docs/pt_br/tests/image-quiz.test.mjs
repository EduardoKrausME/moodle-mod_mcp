/**
 * Regressões do jogo 17 - Quiz de imagens.
 * Execução: node --test docs/pt_br/tests/image-quiz.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-quiz-de-imagens/instrumentos-cientificos.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O jogo precisa conter JavaScript incorporado');
const dataStart=js.indexOf('const DATA='),pureStart=js.indexOf('function normalized(v)'),pureEnd=js.indexOf('// End pure rules.');
assert.ok(dataStart>=0&&pureStart>dataStart&&pureEnd>pureStart,'Regras puras ausentes');
const pure=js.slice(dataStart,js.indexOf('const root=',dataStart))+js.slice(pureStart,pureEnd);
const {DATA,normalized,validate,optionOrder,initialState,score,validState,transition}=
 new Function(pure+'return {DATA,normalized,validate,optionOrder,initialState,score,validState,transition};')();
const act=(s,type,extra={})=>transition(s,{type,...extra},DATA);
const clone=x=>structuredClone(x);

test('HTML completo, responsivo e sem menus, rodapé, scripts ou imagens externas',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<link[^>]+rel="stylesheet"/i);
 assert.match(html,/@media\(max-width:555px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.doesNotThrow(()=>new Script(js));
});

test('seis instrumentos possuem IDs, descrições e gabaritos coerentes',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'image_quiz');
 assert.equal(DATA.questions.length,6);
 assert.equal(new Set(DATA.questions.map(q=>q.correctId)).size,6);
 assert.equal(new Set(DATA.questions.map(q=>q.id)).size,6);
 assert.ok(DATA.questions.every(q=>q.options.length===4));
 assert.ok(DATA.questions.every(q=>q.prompt&&q.explanation&&q.image.alt));
});

test('seis imagens SVG originais têm dados locais, textos alternativos e dimensões 640 × 360',()=>{
 assert.equal(new Set(DATA.questions.map(q=>q.image.src)).size,6);
 for(const q of DATA.questions){
  assert.match(q.image.src,/^data:image\/svg\+xml;charset=utf-8,/);
  const svg=decodeURIComponent(q.image.src.split(',').slice(1).join(','));
  assert.match(svg,/^<svg xmlns="http:\/\/www\.w3\.org\/2000\/svg"/);
  assert.match(svg,/viewBox="0 0 640 360"/);
  assert.match(svg,/<\/svg>$/);
  assert.doesNotMatch(svg.replace('http://www.w3.org/2000/svg',''),/<(?:script|foreignObject|image)\b|https?:\/\/|href=/i);
  assert.ok(q.image.alt.length>35);
  assert.match(q.image.credit,/Ilustração vetorial original/);
 }
 assert.match(js,/img\.width=640;img\.height=360/);
 assert.match(html,/aspect-ratio:16\/9/);
});

test('lupa, microscópio e telescópio têm descrições e desenhos diferentes',()=>{
 const keys=['microscope','magnifier','telescope'];
 const found=keys.map(id=>DATA.questions.find(q=>q.correctId===id));
 assert.ok(found.every(Boolean));
 assert.equal(new Set(found.map(q=>q.image.alt)).size,3);
 assert.equal(new Set(found.map(q=>q.image.src)).size,3);
 assert.match(found[0].image.alt,/lâmina/);
 assert.match(found[1].image.alt,/lente circular/);
 assert.match(found[2].image.alt,/tripé/);
});

test('imagem sem fonte, sem alt ou com URL não prevista não é aceita',()=>{
 const empty=clone(DATA);
 empty.questions[0].image.src='';
 assert.equal(validate(empty),false);
 const missingAlt=clone(DATA);
 missingAlt.questions[0].image.alt='';
 assert.equal(validate(missingAlt),false);
 const remote=clone(DATA);
 remote.questions[0].image.src='https://example.com/microscopio.png';
 assert.equal(validate(remote),false);
});

test('imagens idênticas e descrições alternativas indistinguíveis são rejeitadas',()=>{
 const duplicated=clone(DATA);
 duplicated.questions[1].image.src=duplicated.questions[0].image.src;
 assert.equal(validate(duplicated),false);
 const sameAlt=clone(DATA);
 sameAlt.questions[1].image.alt=sameAlt.questions[0].image.alt;
 assert.equal(validate(sameAlt),false);
 const accent=clone(DATA);
 accent.questions[1].image.alt=sameAlt.questions[0].image.alt.toLocaleUpperCase('pt-BR');
 assert.equal(validate(accent),false);
});

test('correctId inexistente, alternativas duplicadas e labels idênticos falham',()=>{
 const wrong=clone(DATA);
 wrong.questions[0].correctId='unknown';
 assert.equal(validate(wrong),false);
 const repeated=clone(DATA);
 repeated.questions[0].options[1].id=repeated.questions[0].options[0].id;
 assert.equal(validate(repeated),false);
 const duplicateName=clone(DATA);
 duplicateName.questions[0].options[1].label=duplicateName.questions[0].options[0].label;
 assert.equal(validate(duplicateName),false);
 const duplicateQuestion=clone(DATA);
 duplicateQuestion.questions[1].id=duplicateQuestion.questions[0].id;
 assert.equal(validate(duplicateQuestion),false);
});

test('modo inverso exige imagem correspondente e rótulo compatível para cada alternativa',()=>{
 const missingImage=clone(DATA);
 missingImage.questions[0].options[2].id='absent';
 assert.equal(validate(missingImage),false);
 const wrongLabel=clone(DATA);
 wrongLabel.questions[0].options[1].label='Instrumento errado';
 assert.equal(validate(wrongLabel),false);
 const noReverse=clone(DATA);
 noReverse.settings.allowReverse=false;
 assert.equal(validate(noReverse),true);
});

test('ordem aleatória é reproduzível e mantém associação de IDs e nomes',()=>{
 const first=initialState(DATA),again=initialState(DATA);
 assert.deepEqual(first,again);
 assert.equal(first.order.length,6);
 for(let i=0;i<DATA.questions.length;i++){
  const ids=DATA.questions[i].options.map(o=>o.id);
  assert.deepEqual(new Set(first.order[i]),new Set(ids));
  assert.deepEqual(first.order[i],optionOrder(DATA,DATA.questions[i]));
 }
 assert.equal(validState(first,DATA),true);
});

test('desativar embaralhamento mantém ordem original',()=>{
 const fixed=clone(DATA);
 fixed.settings.shuffleOptions=false;
 assert.equal(validate(fixed),true);
 for(const q of fixed.questions)assert.deepEqual(optionOrder(fixed,q),q.options.map(o=>o.id));
 assert.equal(validState(initialState(fixed),fixed),true);
});

test('não avança antes de responder e opção desconhecida não marca resposta',()=>{
 const start=initialState(DATA);
 assert.equal(act(start,'next').changed,false);
 assert.equal(act(start,'choose',{id:'missing'}).changed,false);
 assert.equal(act(start,'choose',{id:DATA.questions[4].correctId}).changed,false);
 assert.equal(start.answers[0],null);
});

test('resposta correta registra um acerto e bloqueia cliques adicionais',()=>{
 let s=initialState(DATA);
 const outcome=act(s,'choose',{id:DATA.questions[0].correctId});
 assert.equal(outcome.changed,true);
 assert.equal(outcome.kind,'right');
 assert.ok(outcome.message.includes(DATA.questions[0].explanation));
 s=outcome.state;
 assert.equal(score(s,DATA),1);
 assert.equal(validState(s,DATA),true);
 assert.equal(act(s,'choose',{id:DATA.questions[0].correctId}).changed,false);
 assert.equal(act(s,'choose',{id:DATA.questions[0].options[1].id}).changed,false);
 s=act(s,'next').state;
 assert.equal(s.index,1);
 assert.equal(validState(s,DATA),true);
});

test('resposta incorreta informa gabarito e razão sem alterar acertos',()=>{
 const q=DATA.questions[0],wrong=q.options.find(o=>o.id!==q.correctId);
 const outcome=act(initialState(DATA),'choose',{id:wrong.id});
 assert.equal(outcome.kind,'wrong');
 assert.equal(score(outcome.state,DATA),0);
 assert.ok(outcome.message.includes(wrong.label));
 assert.ok(outcome.message.includes(q.options.find(o=>o.id===q.correctId).label));
 assert.ok(outcome.message.includes(q.explanation));
 assert.equal(validState(outcome.state,DATA),true);
});

test('trocar de modo conserva resposta, ordem e progresso inclusive após responder',()=>{
 let s=initialState(DATA);
 const reverse=act(s,'mode',{value:'reverse'});
 assert.equal(reverse.changed,true);
 assert.equal(reverse.state.mode,'reverse');
 assert.deepEqual(reverse.state.order,s.order);
 s=reverse.state;
 s=act(s,'choose',{id:DATA.questions[0].correctId}).state;
 const previous=s.answers[0];
 s=act(s,'mode',{value:'identify'}).state;
 assert.equal(s.answers[0],previous);
 assert.equal(s.mode,'identify');
 assert.equal(validState(s,DATA),true);
 assert.equal(act(s,'mode',{value:'invalid'}).changed,false);
 const locked=clone(DATA);
 locked.settings.allowReverse=false;
 assert.equal(transition(initialState(locked),{type:'mode',value:'reverse'},locked).changed,false);
});

test('seis respostas mistas concluem com pontuação e reinício coerentes',()=>{
 let s=initialState(DATA),correct=0;
 for(let i=0;i<DATA.questions.length;i++){
  const q=DATA.questions[i],answer=i%2===0?q.correctId:q.options.find(o=>o.id!==q.correctId).id;
  if(i%2===0)correct++;
  s=act(s,'choose',{id:answer}).state;
  assert.equal(validState(s,DATA),true);
  s=act(s,'next').state;
  assert.equal(validState(s,DATA),true);
 }
 assert.equal(s.completed,true);
 assert.equal(s.index,6);
 assert.equal(score(s,DATA),3);
 assert.equal(correct,3);
 assert.equal(act(s,'choose',{id:'microscope'}).changed,false);
 assert.deepEqual(act(s,'restart').state,initialState(DATA));
});

test('progresso adulterado com resposta externa ou ordem alterada é rejeitado',()=>{
 const out=initialState(DATA);
 out.answers[0]='unknown';
 assert.equal(validState(out,DATA),false);
 const shuffled=initialState(DATA);
 [shuffled.order[0][0],shuffled.order[0][1]]=[shuffled.order[0][1],shuffled.order[0][0]];
 assert.equal(validState(shuffled,DATA),false);
 const skipped=initialState(DATA);
 skipped.index=2;
 assert.equal(validState(skipped,DATA),false);
 const future=initialState(DATA);
 future.answers[3]=DATA.questions[3].correctId;
 assert.equal(validState(future,DATA),false);
 const forced=initialState(DATA);
 forced.completed=true;
 assert.equal(validState(forced,DATA),false);
});

test('imagem com erro de carregamento expõe descrição textual equivalente',()=>{
 assert.match(js,/function buildPicture\(image,kind\)/);
 assert.match(js,/img\.addEventListener\("error"/);
 assert.match(js,/img\.hidden=true;fallback\.hidden=false/);
 assert.match(js,/Imagem indisponível\. Descrição:/);
 assert.match(js,/img\.src=image\.src/);
 assert.match(js,/img\.alt=image\.alt/);
 assert.match(html,/\[hidden\]\{display:none!important\}/);
});

test('UI respeita enunciado específico, teclado, botão e layout mobile',()=>{
 assert.match(js,/state\.mode==="identify"\?q\.prompt/);
 assert.match(html,/id="mode-identify"/);
 assert.match(html,/id="mode-reverse"/);
 assert.match(html,/aria-pressed="true"/);
 assert.match(html,/role="group" aria-label="Alternativas"/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/id="summary-title" tabindex="-1"/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(js,/document\.createElement\(tag\)/);
 assert.match(js,/textContent=value/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
});

test('salvamento opcional usa revisão, limite de 12 KiB e não concede nota',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/TextEncoder/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
});

test('manifestos sincronizados e prompt pergunta antes de gerar o conteúdo',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-quiz-de-imagens/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='quiz-de-imagens-instrumentos-cientificos';
 const a=local.examples.find(e=>e.id===id),b=general.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/17-quiz-de-imagens\.md/);
});

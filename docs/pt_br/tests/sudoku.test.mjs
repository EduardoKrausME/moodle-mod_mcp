/**
 * Regressões do jogo 23 - Sudoku educativo.
 * Execute: node --test docs/pt_br/tests/sudoku.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-sudoku-educativo/laboratorio-dos-padroes.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'Atividade precisa de script inline.');
const pure=js.slice(js.indexOf('const DATA='),js.indexOf('const root='))+js.slice(js.indexOf('function id(r,c)'),js.indexOf('// End pure rules.'));
const {DATA,id,inBounds,sameBox,initialValues,blankNotes,conflicts,candidates,countSolutions,validate,recordOK,isComplete,initialState,validState,transition}=
 new Function(pure+'return {DATA,id,inBounds,sameBox,initialValues,blankNotes,conflicts,candidates,countSolutions,validate,recordOK,isComplete,initialState,validState,transition};')();
const clone=x=>structuredClone(x);
const act=(s,type,props={})=>transition(s,{type,...props},DATA);
const select=(s,r,c)=>act(s,'select',{r,c}).state;
const set=(s,value)=>act(s,'enter',{value}).state;
const solution=[[1,2,3,4,5,6],[4,5,6,1,2,3],[2,3,4,5,6,1],[5,6,1,2,3,4],[3,4,5,6,1,2],[6,1,2,3,4,5]];
const full=()=>{
 let s=initialState(DATA);
 for(let r=0;r<6;r++)for(let c=0;c<6;c++)if(!DATA.givens[r][c])s=set(select(s,r,c),solution[r][c]);
 return s;
};

test('HTML independente, responsivo e com uma única atividade',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:nav|header|footer|img|svg|canvas|picture|audio|video)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<link[^>]+rel="stylesheet"/i);
 assert.match(html,/@media\(max-width:550px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-color-scheme:dark/);
 assert.match(html,/prefers-contrast:more/);
 assert.doesNotThrow(()=>new Script(js));
});

test('Sudoku numérico 6×6 com blocos 2×3 e 15 pistas bloqueadas',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'sudoku');
 assert.equal(DATA.settings.size,6);
 assert.equal(DATA.settings.boxRows,2);
 assert.equal(DATA.settings.boxCols,3);
 assert.equal(DATA.settings.requireUnique,true);
 assert.deepEqual(DATA.symbols,['1','2','3','4','5','6']);
 assert.equal(DATA.givens.length,6);
 assert.ok(DATA.givens.every(row=>row.length===6));
 assert.equal(DATA.givens.flat().filter(Boolean).length,15);
});

test('backtracking encontra exatamente uma solução e não excede o orçamento',()=>{
 const sol=countSolutions(DATA,2);
 assert.equal(sol.count,1);
 assert.equal(sol.truncated,false);
 assert.equal(isComplete(DATA,solution),true);
});

test('uma grade consistente localmente, mas impossível, é rejeitada',()=>{
 const impossible=clone(DATA);
 impossible.givens[1][0]=5;
 assert.equal(conflicts(impossible,impossible.givens).groups.length,0);
 assert.equal(countSolutions(impossible).count,0);
 assert.equal(validate(impossible),false);
});

test('pistas duplicadas na mesma linha, coluna e bloco impedem publicar',()=>{
 const invalid=clone(DATA);
 invalid.givens[0][1]=3;
 assert.ok(conflicts(invalid,invalid.givens).groups.length>0);
 assert.equal(validate(invalid),false);
 const column=clone(DATA);
 column.givens[1][0]=1;
 assert.equal(validate(column),false);
});

test('grade completamente vazia ou completamente preenchida não é um exercício válido',()=>{
 const empty=clone(DATA);
 empty.givens=Array.from({length:6},()=>Array(6).fill(0));
 assert.equal(validate(empty),false);
 const finished=clone(DATA);
 finished.givens=clone(solution);
 assert.equal(validate(finished),false);
});

test('somente tamanhos e fatores de bloco permitidos são aceitos',()=>{
 for(const size of [0,3,5,7,8,10]){
  const invalid=clone(DATA);invalid.settings.size=size;
  assert.equal(validate(invalid),false);
 }
 for(const [boxRows,boxCols] of [[1,6],[3,3],[2,2],[4,2]]){
  const invalid=clone(DATA);invalid.settings.boxRows=boxRows;invalid.settings.boxCols=boxCols;
  assert.equal(validate(invalid),false);
 }
});

test('dimensões incorretas, 0 fora de faixa e caracteres inválidos são rejeitados',()=>{
 const jagged=clone(DATA);jagged.givens[2].pop();assert.equal(validate(jagged),false);
 const missing=clone(DATA);missing.givens.pop();assert.equal(validate(missing),false);
 const negative=clone(DATA);negative.givens[0][0]=-1;assert.equal(validate(negative),false);
 const tooLarge=clone(DATA);tooLarge.givens[0][0]=7;assert.equal(validate(tooLarge),false);
 const stringNumber=clone(DATA);stringNumber.givens[0][0]='1';assert.equal(validate(stringNumber),false);
});

test('símbolos duplicados, ausentes e tamanho de histórico inválido',()=>{
 const duplicate=clone(DATA);duplicate.symbols[1]=duplicate.symbols[0];assert.equal(validate(duplicate),false);
 const short=clone(DATA);short.symbols.pop();assert.equal(validate(short),false);
 const history=clone(DATA);history.settings.maxHistory=0;assert.equal(validate(history),false);
 const extra=clone(DATA);extra.symbols[0]='';assert.equal(validate(extra),false);
});

test('início respeita casas fixas, notas vazias, modo número e estado não concluído',()=>{
 const s=initialState(DATA);
 assert.deepEqual(s.values,DATA.givens);
 assert.equal(s.mode,'value');
 assert.equal(s.solved,false);
 assert.equal(s.selected.r,0);
 assert.equal(s.selected.c,0);
 assert.ok(s.notes.every(row=>row.every(note=>note.length===0)));
 assert.deepEqual(s.history,[]);
 assert.deepEqual(s.redo,[]);
 assert.equal(validState(s,DATA),true);
});

test('pista fixa é somente leitura mesmo após seleção, entrada, limpeza e colagem lógica',()=>{
 let s=initialState(DATA);
 assert.equal(DATA.givens[0][0],1);
 assert.equal(act(s,'enter',{value:6}).changed,false);
 assert.equal(act(s,'clear').changed,false);
 s=act(s,'mode',{mode:'note'}).state;
 assert.equal(act(s,'enter',{value:3}).changed,false);
 assert.equal(s.values[0][0],1);
 assert.deepEqual(s.notes[0][0],[]);
 assert.equal(validState(s,DATA),true);
});

test('pistas fixas podem receber foco de navegação sem permitir alteração',()=>{
 let s=select(initialState(DATA),0,1);
 s=act(s,'move',{direction:'left'}).state;
 assert.deepEqual(s.selected,{r:0,c:0});
 assert.equal(act(s,'enter',{value:5}).changed,false);
 assert.deepEqual(s.values,DATA.givens);
});

test('digitação válida substitui imediatamente valor preenchido',()=>{
 let s=select(initialState(DATA),0,1);
 s=set(s,2);
 assert.equal(s.values[0][1],2);
 s=set(s,6);
 assert.equal(s.values[0][1],6);
 assert.equal(s.history.length,2);
 assert.equal(validState(s,DATA),true);
 assert.equal(act(s,'enter',{value:6}).changed,false);
});

test('símbolo inválido e clique duplo no mesmo valor não mudam estado',()=>{
 const s=select(initialState(DATA),0,1);
 assert.equal(act(s,'enter',{value:0}).changed,false);
 assert.equal(act(s,'enter',{value:7}).changed,false);
 assert.equal(act(s,'enter',{value:'3'}).changed,false);
 assert.equal(act(s,'enter',{value:1.5}).changed,false);
 const one=set(s,4);
 assert.equal(act(one,'enter',{value:4}).changed,false);
 assert.equal(one.history.length,1);
});

test('Backspace/Delete apaga apenas células editáveis e permite refazer',()=>{
 let s=set(select(initialState(DATA),0,1),2);
 s=act(s,'clear').state;
 assert.equal(s.values[0][1],0);
 assert.equal(validState(s,DATA),true);
 s=act(s,'undo').state;
 assert.equal(s.values[0][1],2);
 assert.equal(validState(s,DATA),true);
});

test('anotações alternam números sem substituir o valor definitivo',()=>{
 let s=set(select(initialState(DATA),0,1),2);
 s=act(s,'mode',{mode:'note'}).state;
 s=set(s,3);
 s=set(s,5);
 assert.equal(s.values[0][1],2);
 assert.deepEqual(s.notes[0][1],[3,5]);
 assert.equal(validState(s,DATA),true);
 s=set(s,3);
 assert.equal(s.values[0][1],2);
 assert.deepEqual(s.notes[0][1],[5]);
});

test('voltar a números substitui valor e limpa anotações antigas',()=>{
 let s=select(initialState(DATA),0,1);
 s=act(s,'mode',{mode:'note'}).state;
 s=set(s,3);
 s=set(s,5);
 s=act(s,'mode',{mode:'value'}).state;
 s=set(s,2);
 assert.equal(s.values[0][1],2);
 assert.deepEqual(s.notes[0][1],[]);
 assert.equal(validState(s,DATA),true);
});

test('Backspace em casa vazia apaga anotações em uma única alteração',()=>{
 let s=select(initialState(DATA),0,1);
 s=act(s,'mode',{mode:'note'}).state;
 s=set(s,2);
 const before=s.history.length;
 s=act(s,'clear').state;
 assert.deepEqual(s.notes[0][1],[]);
 assert.equal(s.history.length,before+1);
 assert.equal(s.values[0][1],0);
});

test('detecção de conflito múltiplo mostra linha, coluna e bloco e todas as casas',()=>{
 const s=set(select(initialState(DATA),1,0),6);
 const problems=conflicts(DATA,s.values),reasons=problems.cells.get('1,0');
 assert.ok(reasons);
 assert.deepEqual([...reasons].sort(),['bloco L1-2/C1-3','coluna 1','linha 2']);
 for(const k of ['1,0','1,2','5,0']){
  assert.ok(problems.cells.has(k),'Casa '+k+' também deve estar destacada.');
 }
 assert.equal(problems.groups.length,3);
 assert.equal(validState(s,DATA),true);
});

test('conflito não equivale a erro contra gabarito de solução',()=>{
 let s=set(select(initialState(DATA),1,0),5);
 assert.equal(conflicts(DATA,s.values).groups.length,0);
 assert.equal(isComplete(DATA,s.values),false);
 assert.equal(act(s,'check').changed,false);
 assert.match(act(s,'check').message,/faltam/);
});

test('dica apresenta apenas candidatos locais e não altera respostas',()=>{
 const s=select(initialState(DATA),0,1),before=clone(s);
 const out=act(s,'hint');
 assert.equal(out.changed,false);
 assert.match(out.message,/valores permitidos pelas regras locais/);
 assert.match(out.message,/não revela o gabarito/);
 assert.deepEqual(out.state,before);
 assert.deepEqual(candidates(DATA,s.values,0,1),[2]);
});

test('dica sobre pista fixa não sugere alteração',()=>{
 const s=initialState(DATA),out=act(s,'hint');
 assert.equal(out.changed,false);
 assert.match(out.message,/pista fixa/);
 assert.equal(validState(s,DATA),true);
});

test('setas navegam uma posição lógica sem modificar números ou anotações',()=>{
 let s=initialState(DATA),original=clone(s.values);
 s=act(s,'move',{direction:'right'}).state;
 assert.deepEqual(s.selected,{r:0,c:1});
 s=act(s,'move',{direction:'down'}).state;
 assert.deepEqual(s.selected,{r:1,c:1});
 s=act(s,'move',{direction:'left'}).state;
 assert.deepEqual(s.selected,{r:1,c:0});
 s=act(s,'move',{direction:'up'}).state;
 assert.deepEqual(s.selected,{r:0,c:0});
 assert.deepEqual(s.values,original);
 assert.equal(validState(s,DATA),true);
});

test('setas na borda não causam wrap nem mudança de valores',()=>{
 const start=initialState(DATA);
 assert.equal(act(start,'move',{direction:'up'}).changed,false);
 assert.equal(act(start,'move',{direction:'left'}).changed,false);
 const far=select(start,5,5);
 assert.equal(act(far,'move',{direction:'down'}).changed,false);
 assert.equal(act(far,'move',{direction:'right'}).changed,false);
 assert.deepEqual(far.values,start.values);
});

test('undo/redo restauram valores e notas, com foco lógico na célula alterada',()=>{
 let s=select(initialState(DATA),0,1);
 s=set(s,2);
 s=act(s,'mode',{mode:'note'}).state;
 s=set(s,3);
 s=set(s,5);
 assert.deepEqual(s.notes[0][1],[3,5]);
 s=act(s,'undo').state;
 assert.deepEqual(s.notes[0][1],[3]);
 assert.deepEqual(s.selected,{r:0,c:1});
 assert.equal(validState(s,DATA),true);
 s=act(s,'undo').state;
 assert.deepEqual(s.notes[0][1],[]);
 assert.equal(s.values[0][1],2);
 assert.equal(validState(s,DATA),true);
 s=act(s,'redo').state;
 assert.deepEqual(s.notes[0][1],[3]);
 assert.equal(s.values[0][1],2);
 assert.equal(validState(s,DATA),true);
});

test('nova entrada após undo descarta histórico de redo',()=>{
 let s=select(initialState(DATA),0,1);
 s=set(s,2);
 s=set(s,6);
 s=act(s,'undo').state;
 assert.equal(s.redo.length,1);
 s=set(s,5);
 assert.equal(s.redo.length,0);
 assert.equal(act(s,'redo').changed,false);
 assert.equal(s.values[0][1],5);
});

test('histórico é limitado a maxHistory sem perder consistência do estado',()=>{
 let s=select(initialState(DATA),0,1);
 for(let i=0;i<80;i++)s=set(s,i%2+1);
 assert.equal(s.history.length,DATA.settings.maxHistory);
 assert.equal(s.values[0][1],2);
 assert.equal(validState(s,DATA),true);
});

test('histórico ou notas adulteradas são rejeitados na restauração',()=>{
 const given=initialState(DATA);given.values[0][0]=6;assert.equal(validState(given,DATA),false);
 const fixedNotes=initialState(DATA);fixedNotes.notes[0][0]=[2];assert.equal(validState(fixedNotes,DATA),false);
 const duplicates=initialState(DATA);duplicates.notes[0][1]=[2,2];assert.equal(validState(duplicates,DATA),false);
 const unsorted=initialState(DATA);unsorted.notes[0][1]=[3,1];assert.equal(validState(unsorted,DATA),false);
 const unknown=initialState(DATA);unknown.notes[0][1]=[7];assert.equal(validState(unknown,DATA),false);
 const forced=initialState(DATA);forced.solved=true;assert.equal(validState(forced,DATA),false);
 const tampered=initialState(DATA);tampered.history=[{r:0,c:0,before:{value:1,notes:[]},after:{value:2,notes:[]}}];assert.equal(validState(tampered,DATA),false);
});

test('mudança de símbolo e anotações pode ser restaurada como JSON',()=>{
 let s=select(initialState(DATA),2,2);
 s=set(s,4);
 s=act(s,'mode',{mode:'note'}).state;
 s=set(s,1);
 const parsed=JSON.parse(JSON.stringify(s));
 assert.deepEqual(parsed,s);
 assert.equal(validState(parsed,DATA),true);
 assert.equal(parsed.values[2][2],4);
 assert.deepEqual(parsed.notes[2][2],[1]);
});

test('verificar grade incompleta ou com conflitos não atribui conclusão',()=>{
 let s=initialState(DATA);
 const incomplete=act(s,'check');
 assert.equal(incomplete.changed,false);
 assert.match(incomplete.message,/faltam/);
 s=set(select(s,1,0),6);
 const conflicted=act(s,'check');
 assert.equal(conflicted.changed,false);
 assert.match(conflicted.message,/símbolos repetidos/);
 assert.equal(s.solved,false);
});

test('solução completa e consistente permite concluir sem gabarito no estado',()=>{
 let s=full();
 assert.equal(s.values.flat().filter(Boolean).length,36);
 assert.equal(conflicts(DATA,s.values).groups.length,0);
 assert.equal(validState(s,DATA),true);
 assert.equal(isComplete(DATA,s.values),true);
 assert.equal(s.solved,false);
 const completed=act(s,'check');
 assert.equal(completed.changed,true);
 assert.equal(completed.kind,'good');
 assert.equal(completed.state.solved,true);
 assert.equal(validState(completed.state,DATA),true);
 assert.equal(Object.hasOwn(completed.state,'solution'),false);
 assert.equal(act(completed.state,'undo').changed,false);
 assert.equal(act(completed.state,'enter',{value:3}).changed,false);
});

test('reiniciar mantém apenas as pistas e limpa anotações, histórico e conclusão',()=>{
 let s=select(initialState(DATA),0,1);
 s=set(s,2);
 s=act(s,'mode',{mode:'note'}).state;
 s=set(s,3);
 const reset=act(s,'restart');
 assert.equal(reset.changed,true);
 assert.deepEqual(reset.state,initialState(DATA));
 assert.equal(reset.state.notes[0][1].length,0);
 assert.equal(validState(reset.state,DATA),true);
});

test('entrada numérica por teclado físico e touch utiliza a mesma transição',()=>{
 assert.match(js,/data-number/);
 assert.match(js,/handleAction\(\{type:"enter",value:Number\(b\.dataset\.number\)\}\)/);
 assert.match(js,/const index=DATA\.symbols\.indexOf\(event\.key\)/);
 assert.match(js,/handleAction\(\{type:"enter",value:index\+1\}\)/);
 assert.match(js,/event\.isComposing/);
 assert.match(js,/event\.key==="Backspace"\|\|event\.key==="Delete"/);
 assert.match(js,/DATA\.symbols\.includes\(text\)/);
 assert.match(js,/event\.preventDefault\(\)/);
});

test('foco é estável, sem reconstruir grade em cada tecla',()=>{
 assert.match(js,/function buildBoard\(/);
 assert.match(js,/function render\(/);
 assert.match(js,/b\.tabIndex=here\?0:-1/);
 assert.match(js,/function focusSelected\(/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(js,/for\(const b of buttons\)/);
 assert.doesNotMatch(js.slice(js.indexOf('function render(){'),js.indexOf('function save(){')),/board\.replaceChildren/);
 assert.match(html,/Tab e Shift\+Tab/);
});

test('regras de acessibilidade, cores e informação textual dos conflitos',()=>{
 assert.match(html,/role="group" aria-label="Grade Sudoku/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/id="selection-detail"/);
 assert.match(html,/id="conflicts"[^>]+aria-label/);
 assert.match(html,/id="note-mode"[^>]+aria-pressed="false"/);
 assert.match(js,/setAttribute\("aria-label","Linha "/);
 assert.match(js,/function conflictText\(problems\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-contrast:more/);
 assert.match(html,/@media\(max-width:550px\)/);
});

test('dados de autoria são renderizados apenas como texto, sem executar HTML',()=>{
 assert.match(js,/document\.createElement\(tag\)/);
 assert.match(js,/n\.textContent=text/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
 assert.doesNotMatch(js,/eval\(|new Function\(/);
 const dangerous=clone(DATA);
 dangerous.title='<img src=x onerror=alert(1)>';
 assert.equal(validate(dangerous),true);
});

test('persistência opcional é versionada e nunca aciona notas oficiais',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/TextEncoder/);
 assert.match(js,/12288/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
});

test('reinício exige confirmação quando já há jogadas',()=>{
 assert.match(html,/id="reset-confirm"/);
 assert.match(html,/id="confirm-reset"/);
 assert.match(html,/id="cancel-reset"/);
 assert.match(js,/function requestRestart\(/);
 assert.match(js,/if\(changed\)\{\$\("reset-confirm"\)\.hidden=false/);
 assert.match(js,/Reinício cancelado\. O progresso foi mantido\./);
});

test('JSON local e catálogo global têm a mesma entrada e aguardam o professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-sudoku-educativo/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='sudoku-educativo-laboratorio-dos-padroes';
 const a=local.examples.find(x=>x.id===id),b=global.examples.find(x=>x.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/23-sudoku-educativo\.md/);
 assert.match(a.prompt,/\.chatgpt\/games\/RULES\.md/);
});

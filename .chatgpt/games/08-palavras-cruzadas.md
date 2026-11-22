# 08. Palavras cruzadas

**Tipo proposto:** `crossword` | **Dificuldade de implementação:** Médio

## Finalidade e experiência do aluno

Termos e definições com recuperação ativa.

O aluno preenche palavras entrecruzadas na horizontal e vertical a partir de pistas numeradas.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "crossword",
  "title": "Informática",
  "entries": [
    {
      "id": "cpu",
      "answer": "CPU",
      "clue": "Unidade central de processamento",
      "row": 0,
      "col": 0,
      "direction": "across"
    },
    {
      "id": "pixel",
      "answer": "PIXEL",
      "clue": "Menor elemento de uma imagem digital",
      "row": 0,
      "col": 1,
      "direction": "down"
    }
  ]
}
```

Usar `activitytype="crossword"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Preferir grade já posicionada no JSON; validar cada entrada, limites, colisões, letras compatíveis nos cruzamentos e numeração.
2. Gerar células editáveis únicas e mapear cada célula às palavras que a atravessam; setas mudam foco, Backspace apaga e Tab percorre pistas.
3. Destacar a palavra ativa sem ocultar outras; ao digitar em cruzamento, atualizar uma única célula compartilhada.
4. Implementar as regras obrigatórias de digitação e avanço detalhadas abaixo, sem depender do comportamento padrão de um input preenchido.
5. Verificar palavras por resposta normalizada, sem exigir validação global a cada tecla.

## Regras obrigatórias da digitação e do foco

Estas regras fazem parte do funcionamento mínimo do jogo gerado. Um teclado de palavras cruzadas deve parecer natural: o aluno não precisa apagar a letra anterior para corrigir uma resposta.

1. **Substituir sempre a letra existente.** Quando uma célula tem uma letra e recebe outra letra pelo teclado físico ou virtual, a nova letra substitui imediatamente a anterior, mesmo que o cursor não esteja com o caractere selecionado. Nunca concatenar, ignorar a digitação por causa do `maxlength=1` nem exigir Backspace antes da troca. Cada célula guarda apenas um caractere válido.
2. **Avançar sempre após digitar uma letra válida.** Depois de inserir ou substituir, mover o foco automaticamente para a próxima célula da **palavra ativa**, de acordo com a orientação horizontal ou vertical selecionada. Avançar inclusive quando o próximo campo já estiver preenchido, deixando-o pronto para outra substituição. Não saltar células preenchidas.
3. **Respeitar os limites da palavra.** Não levar o foco à palavra vizinha, à célula preta nem a uma posição fora do tabuleiro. Na última casa da palavra, permanecer nela após a digitação, sinalizar que a palavra está preenchida e permitir verificação ou escolha da próxima pista.
4. **Manter a direção ativa nos cruzamentos.** Uma célula compartilhada por duas palavras armazena uma única letra. Digitar nela atualiza ambas as palavras, mas o avanço segue apenas a palavra selecionada. Selecionar uma pista horizontal/vertical define a direção; tocar ou clicar novamente numa célula de cruzamento pode alternar a orientação, de forma visível e anunciada.
5. **Backspace previsível.** Se a célula focada contém letra, Backspace apaga somente essa letra e mantém o foco nela; se está vazia, Backspace volta uma casa da palavra ativa e apaga a letra dessa casa, caso exista. Delete apaga a célula atual sem mudar o foco. Nunca apagar automaticamente uma palavra inteira.
6. **Setas para navegação, não para resposta.** Setas movem o foco entre casas editáveis conforme a grade, sem alterar letras; em cruzamentos, selecionar orientação com pista ou gesto explícito. Não interceptar Tab de maneira que impeça a saída do jogo por teclado: Tab e Shift+Tab precisam seguir uma ordem de foco acessível.
7. **Teclado físico, virtual e IME.** Tratar entrada por `beforeinput`/`input` e eventos de composição quando necessário, evitando dupla inserção/avanço pelo mesmo caractere. A normalização para maiúscula e o tratamento de acentos devem ser consistentes com a língua do jogo. Não avançar no meio de uma composição; avançar uma vez quando um caractere válido for confirmado.
8. **Colar e caracteres inválidos.** Nunca espalhar texto colado silenciosamente em campos aleatórios: ou distribuir conscientemente as letras pelas casas restantes da palavra ativa, ou rejeitar a colagem com instrução acessível. Ignorar teclas de controle, entradas inválidas e ações que não mudam a resposta, sem avançar o foco.
9. **Foco visual e seleção.** Toda célula ativa deve ter contorno visível, e a palavra correspondente deve estar destacada. Ao receber foco numa célula já preenchida, selecionar visualmente o caractere quando possível, mas **não depender dessa seleção** para garantir a substituição.
10. **Retomada coerente.** Salvar células por coordenada/ID, palavra ativa e orientação; ao restaurar, recompor a mesma grade, mas não deslocar o foco inesperadamente para outra palavra. O foco salvo é uma preferência, não uma razão para roubar o foco de outro elemento do Moodle.

### Algoritmo mínimo esperado

O estado da grade deve ser a fonte de verdade. Não utilizar `input.value += letra` e não depender apenas de `maxlength` para controlar a substituição.

```javascript
function applyLetter(cellId, letter) {
    const character = normalizeLetter(letter); // Um caractere aceito no idioma configurado.
    if (!character) {
        return; // Entrada inválida: não altera a célula nem avança.
    }

    state.cells[cellId] = character; // Sobrescreve, mesmo se já havia uma letra.
    renderCell(cellId, character);

    const next = nextCellInActiveEntry(cellId, state.activeEntryId);
    if (next) {
        focusCell(next); // Avança mesmo se next já estiver preenchida.
    } else {
        focusCell(cellId); // Última letra: permanece na palavra atual.
    }
}
```

`normalizeLetter`, `renderCell`, `nextCellInActiveEntry` e `focusCell` são funções a implementar no HTML gerado; o trecho acima descreve o contrato, não uma API preexistente. A captura de eventos deve evitar processar a mesma digitação em duplicidade e preservar letras de cruzamentos. Não recriar toda a grade a cada tecla de modo que o campo perca o foco.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Verificar palavra ou jogo completo sob demanda; explicar a pista após resolução.
- **Acessibilidade e mobile:** Lista de pistas separada, navegação por teclado, rótulos linha/coluna e leitura das letras sem anunciar respostas.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Mapa célula->letra, palavras verificadas, dicas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Cruzamento incompatível, palavra fora da grade, Backspace, teclado móvel, foco e caracteres acentuados. Cobrir especialmente: digitar "A" numa célula já preenchida com "P" resulta em "A" (não "PA", nem "P") e foco na casa seguinte; inserir uma letra em casa vazia também avança; o avanço chega a uma casa preenchida sem pulá-la; última casa não salta para outra palavra; cruzamento segue orientação ativa; Backspace em casa preenchida limpa sem voltar e em casa vazia volta uma casa; Delete limpa sem avanço; teclas de navegação não mudam o conteúdo; IME e mobile não produzem avanço duplo; colagem tem comportamento explícito; Tab/Shift+Tab continuam acessíveis.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade palavras cruzadas para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/08-palavras-cruzadas.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

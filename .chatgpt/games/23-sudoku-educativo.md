# 23. Sudoku educativo

**Tipo proposto:** `sudoku` | **Dificuldade de implementação:** Médio

## Finalidade e experiência do aluno

Raciocínio lógico, dedução e padrões.

Completar uma matriz 4x4, 6x6 ou 9x9 sem repetir símbolos em linhas, colunas e blocos; pode usar números, letras ou ícones.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "sudoku",
  "title": "Sudoku 4x4",
  "settings": {
    "size": 4,
    "boxRows": 2,
    "boxCols": 2
  },
  "givens": [
    [
      1,
      0,
      0,
      4
    ],
    [
      0,
      4,
      1,
      0
    ],
    [
      0,
      1,
      4,
      0
    ],
    [
      4,
      0,
      0,
      1
    ]
  ],
  "symbols": [
    "1",
    "2",
    "3",
    "4"
  ]
}
```

Usar `activitytype="sudoku"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Validar tamanho, dimensões dos blocos, símbolos, casas fixas e consistência inicial.
2. Gerar grade de inputs ou botões, impedir edição de givens e verificar repetições em linha, coluna e caixa.
3. Para puzzles gerados, resolver por backtracking e garantir unicidade quando essa promessa for feita.
4. Diferenciar verificação local de regras e verificação da solução inteira; não exigir solução única se o enunciado não a assegura.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Substituir número imediatamente:** ao digitar um símbolo válido numa casa editável já preenchida, sobrescrever o valor anterior; não concatenar, exigir apagar antes ou avançar de forma inesperada.
2. **Casas iniciais bloqueadas:** valores de `givens` são somente leitura. Clique, digitação, colagem ou limpeza não podem alterar uma casa fixa; distingui-la visualmente e em rótulo acessível.
3. **Limpeza e anotações:** Backspace/Delete removem o valor apenas de célula editável; quando modo anotações está ativo, seleção alterna a anotação sem trocar o valor definitivo.
4. **Conflitos claros:** após edição, identificar conflitos da mesma linha, coluna e caixa; realçar todas as casas envolvidas e explicar textualmente, sem marcar como 'erro contra gabarito' quando a regra só detecta duplicação.
5. **Navegação:** setas movem seleção entre casas, respeitando bordas, sem alterar símbolos. Teclado numérico touch e físico acionam mesma função; não roubar Tab/Shift+Tab.
6. **Desfazer/refazer:** guardar alterações em pilha limitada; desfazer restaura valor e anotações sem afetar casas fixas; reiniciar preserva apenas os dados iniciais.
7. **Solução válida:** verificar preenchimento completo e ausência de violações; se a atividade exigir solução única, validar unicidade por solver antes de disponibilizar o tabuleiro.
8. **Testes:** casa fixa, substituição, Backspace, conflito múltiplo, notas, undo/redo, setas, grade inválida e mobile.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Realçar conflitos por linha/coluna/bloco e indicar grade completa sem revelar solução prematuramente.
- **Acessibilidade e mobile:** Teclado numérico visível, coordenadas célula, seletor de símbolos e comandos de navegação por setas.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Valores editados, erros e conclusão; não salvar solução completa quando desnecessário.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Grade impossível, símbolos duplicados, dimensão inválida, celular, casas fixas e undo.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade sudoku educativo para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/23-sudoku-educativo.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

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

# 11. Bingo educativo

**Tipo proposto:** `bingo` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Revisão e reconhecimento rápido de conteúdos.

O aluno recebe uma cartela com respostas; são apresentadas definições, imagens ou perguntas sorteadas e ele marca a célula que corresponde à pista.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "bingo",
  "title": "Geometria",
  "settings": {
    "size": 3,
    "win": "row_column_diagonal",
    "seed": "lesson-1"
  },
  "entries": [
    {
      "id": "tri",
      "label": "Triângulo",
      "clue": "Figura com três lados"
    },
    {
      "id": "sq",
      "label": "Quadrado",
      "clue": "Quatro lados iguais"
    },
    {
      "id": "circle",
      "label": "Círculo",
      "clue": "Região delimitada por circunferência"
    }
  ]
}
```

Usar `activitytype="bingo"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Gerar cartela de tamanho declarado com pool suficiente de itens e seed estável; opcionalmente usar casa livre.
2. Sortear pistas sem repetição e expor uma por vez; registrar ordem dos sorteios.
3. Permitir marcar apenas itens efetivamente chamados, checando correspondência por ID.
4. Detectar vitória pela regra configurada (linha, coluna, diagonal ou cartela cheia) em vez de assumir qualquer linha.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Exibir pista atual e histórico de chamadas; celebrar linha concluída sem som obrigatório.
- **Acessibilidade e mobile:** Grade com coordenadas e controles acessíveis; anúncio da pista textual e histórico consultável.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Cartela gerada, itens chamados, casas marcadas e estado de vitória.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Pool menor que cartela, chamada repetida, vitória diagonal, recarregar no meio e múltiplas cartelas.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade bingo educativo para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/11-bingo-educativo.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

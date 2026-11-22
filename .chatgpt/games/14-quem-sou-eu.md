# 14. Quem sou eu?

**Tipo proposto:** `who_am_i` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Identificação de conceitos por inferência progressiva.

Dicas são reveladas uma a uma; o aluno tenta descobrir um conceito, pessoa histórica, objeto ou fenômeno com o menor número de dicas.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "who_am_i",
  "title": "Astronomia",
  "rounds": [
    {
      "id": "sun",
      "answer": "Sol",
      "aliases": [
        "o sol"
      ],
      "clues": [
        "Sou uma estrela.",
        "Fico no centro do Sistema Solar."
      ]
    }
  ],
  "settings": {
    "maxGuesses": 3
  }
}
```

Usar `activitytype="who_am_i"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Manter dicas em ordem do mais amplo ao mais específico; validar answer e pistas não vazias.
2. Exibir input de resposta e botão de revelar a próxima dica; normalizar aliases aceitos.
3. Pontuar ludicamente conforme dicas consumidas, sem apresentar métrica como nota Moodle.
4. Encerrar quando acertar, esgotar tentativas ou revelar todas as pistas; fornecer opção de desistir.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Mostrar pistas já abertas, comentários sobre a resposta e resposta completa ao finalizar.
- **Acessibilidade e mobile:** Controles de revelar dicas e tentar, com status textual; sem dependência de temporizador.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Rodada, índice de dica, tentativas, respostas dadas e acertos.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Apelidos/sinônimos, acentos, tentativas esgotadas, dicas vazias e recuperação de sessão.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade quem sou eu? para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/14-quem-sou-eu.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

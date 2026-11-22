# 22. Batalha de perguntas

**Tipo proposto:** `quiz_battle` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Revisão gamificada individual com rodadas e metas.

O aluno responde uma sequência de perguntas para acumular pontos lúdicos, manter combos ou avançar contra um relógio opcional; adversário é visual ou simulado, nunca outro aluno sem infraestrutura própria.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "quiz_battle",
  "title": "Matemática",
  "settings": {
    "mode": "solo",
    "timeLimitSeconds": 0,
    "pointsCorrect": 10
  },
  "rounds": [
    {
      "id": "q1",
      "prompt": "2 + 2 = ?",
      "options": [
        {
          "id": "3",
          "label": "3"
        },
        {
          "id": "4",
          "label": "4"
        }
      ],
      "correctId": "4"
    }
  ]
}
```

Usar `activitytype="quiz_battle"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Reusar mecanismo de pergunta de escolha única e acrescentar camada de placar, combos e transições.
2. Separar relógio de avaliação: timeLimitSeconds=0 desativa tempo; não prometer cronômetro confiável offline.
3. Controlar resposta única por rodada, atualizar placar determinístico e avançar mediante feedback.
4. Em eventual competição real, exigir serviço no servidor para ranking, relógio e antifraude.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Placar visível, sequência de acertos e explicação após cada rodada.
- **Acessibilidade e mobile:** Modo sem tempo, redução de movimento, botões grandes e atualização do placar sem roubar foco.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Rodada, respostas, combo, placar e eventual tempo restante lúdico.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Tempo expirado, clique duplo, placar após retomada, empate e sessão sem áudio.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade batalha de perguntas para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/22-batalha-de-perguntas.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

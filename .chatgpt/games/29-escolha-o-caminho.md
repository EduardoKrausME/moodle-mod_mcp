# 29. Escolha o caminho

**Tipo proposto:** `branching_story` | **Dificuldade de implementação:** Médio

## Finalidade e experiência do aluno

Tomada de decisão, consequências e pensamento crítico.

O aluno lê uma situação e escolhe entre decisões. Cada escolha leva a uma nova cena, consequência ou encerramento, podendo haver mais de um final válido.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "branching_story",
  "title": "Atendimento ao cliente",
  "start": "scene1",
  "scenes": [
    {
      "id": "scene1",
      "text": "O cliente recebeu produto errado.",
      "options": [
        {
          "id": "a",
          "label": "Ouvir e confirmar pedido",
          "next": "good"
        },
        {
          "id": "b",
          "label": "Ignorar",
          "next": "bad"
        }
      ]
    },
    {
      "id": "good",
      "text": "O atendimento prossegue adequadamente.",
      "ending": "success",
      "options": []
    },
    {
      "id": "bad",
      "text": "O problema aumenta.",
      "ending": "review",
      "options": []
    }
  ]
}
```

Usar `activitytype="branching_story"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Construir grafo dirigido com cenas e escolhas; validar next existentes, início definido e ao menos um final alcançável.
2. Renderizar situação e opções em botões; bloquear resposta duplicada no mesmo nó e navegar para cena seguinte.
3. Guardar caminho completo como pares cena/escolha e suportar retomar em qualquer cena válida.
4. Se o professor desejar pontuação, usar rubrica por decisão; evitar tratar um único final como resposta correta quando existem consequências abertas.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Decisão única por visita:** escolher uma opção leva somente ao `next` correspondente; clique duplo não visita duas cenas nem registra escolhas duplicadas.
2. **Histórico reconstruível:** armazenar sequencialmente cena, escolha e destino; ao retomar, renderizar cena atual e consequências sem executar novamente efeitos de escolhas anteriores.
3. **Voltar com política explícita:** quando permitido, voltar até decisão anterior restaura o ponto do caminho e invalida somente escolhas descendentes reescritas, sem misturar dois ramos incompatíveis.
4. **Ciclos sob controle:** limitar repetições de cenas ou exigir condição de saída; gerar cenário com ao menos um final alcançável e evitar loop sem término.
5. **Finais múltiplos:** não classificar automaticamente como erro qualquer final diferente de `success`; explicar as consequências, critérios e possibilidades pedagógicas.
6. **Reiniciar uma história:** começar novo percurso zera escolhas desta tentativa, mas não modifica histórico de outra tentativa armazenada.
7. **Navegação acessível:** texto completo da cena, botões por escolha, foco previsível após mudança e opção de revisar decisões passadas.
8. **Testes:** duas escolhas rápidas, voltar e mudar de ramo, história cíclica, final sem opções, retomar e múltiplos finais válidos.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Mostrar consequência imediata e reflexão sobre a decisão, não apenas 'certo/errado'.
- **Acessibilidade e mobile:** Texto linear, botões claros e opção de rever caminhos anteriores sem perda acidental.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Cena atual, trilha de escolhas, finais visitados e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Ciclo infinito, final sem opções, nó inalcançável, regressão de cena e muitas ramificações.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade escolha o caminho para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/29-escolha-o-caminho.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

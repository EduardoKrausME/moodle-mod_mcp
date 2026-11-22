# 27. Escape room educativo

**Tipo proposto:** `escape_room` | **Dificuldade de implementação:** Médio

## Finalidade e experiência do aluno

Resolução integrada de enigmas, cooperação opcional e aplicação de conhecimentos.

O aluno atravessa salas ou cenas, localiza pistas, resolve enigmas e desbloqueia cadeados até concluir uma missão.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "escape_room",
  "title": "Missão laboratório",
  "rooms": [
    {
      "id": "lab",
      "title": "Laboratório",
      "clues": [
        {
          "id": "c1",
          "text": "A água ferve a 100 °C ao nível do mar."
        }
      ],
      "locks": [
        {
          "id": "lock1",
          "prompt": "Qual é a temperatura em °C?",
          "answer": "100",
          "unlocks": "exit"
        }
      ]
    },
    {
      "id": "exit",
      "title": "Saída",
      "clues": [],
      "locks": []
    }
  ],
  "settings": {
    "startRoom": "lab",
    "finishRoom": "exit"
  }
}
```

Usar `activitytype="escape_room"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Modelar salas como grafo, pistas como itens colecionáveis e cadeados com condições explícitas.
2. Validar referências de rooms e unlocks, verificar que existe caminho solucionável até a saída.
3. Renderizar uma sala por vez, lista de pistas, inventário e formulário de resposta; não depender de clicar em pixels pequenos.
4. Guardar flags de cadeados abertos e inventário; evitar repetir recompensas e proteger de transições sem requisito.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Explicar a solução de cada enigma quando permitido; criar pistas graduais para evitar abandono.
- **Acessibilidade e mobile:** Todas as pistas e objetos devem existir em lista textual clicável; alternativa a cadeados com drag.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Sala atual, inventário, enigmas resolvidos, pistas reveladas e fim da missão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Enigma impossível, porta com referência inexistente, fechamento de sala, retomar e enunciados inacessíveis.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade escape room educativo para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/27-escape-room-educativo.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

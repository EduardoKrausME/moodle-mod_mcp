# 03. Arrastar e associar

**Tipo proposto:** `drag_match` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Relacionar conceitos, imagens, definições e funções.

Itens embaralhados ficam em uma coluna e destinos na outra. O aluno arrasta cada item até o destino correspondente ou seleciona origem e destino com dois toques.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "drag_match",
  "title": "Hardware",
  "settings": {
    "shuffle": true,
    "retryWrong": true
  },
  "items": [
    {
      "id": "cpu",
      "label": "Processador",
      "targetId": "compute"
    },
    {
      "id": "ram",
      "label": "Memória RAM",
      "targetId": "volatile"
    }
  ],
  "targets": [
    {
      "id": "compute",
      "label": "Executa instruções"
    },
    {
      "id": "volatile",
      "label": "Armazenamento temporário"
    }
  ]
}
```

Usar `activitytype="drag_match"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Validar que todos os targetId referenciam destinos existentes e que a capacidade dos alvos é definida (um item ou vários).
2. Renderizar origens como botões e destinos como regiões identificáveis; usar Pointer Events ou controles acessíveis, não confiar apenas no HTML Drag and Drop nativo em touch.
3. Calcular posição pelo destino no release, ativar destaque visual do alvo e cancelar corretamente quando soltar fora.
4. Comparar IDs, fixar associação válida ou permitir tentar novamente; suportar desfazer e reiniciar conforme configuração.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Mostrar associação aceita ou motivo pedagógico do erro; não depender apenas de cores.
- **Acessibilidade e mobile:** Fluxo alternativo 'Selecionar item' e 'Escolher destino'; teclados, leitores de tela e alvos com tamanho adequado.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Mapa itemId->targetId confirmado, tentativas, erros e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Item solto fora, alvo ocupado, multitouch, troca de orientação, entradas duplicadas e desfazer.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade arrastar e associar para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/03-arrastar-e-associar.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

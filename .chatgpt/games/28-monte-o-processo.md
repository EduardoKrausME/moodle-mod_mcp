# 28. Monte o processo

**Tipo proposto:** `process_builder` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Construção de fluxos e compreensão de dependências.

O aluno arrasta blocos de um procedimento para um fluxo, conectando etapas e decisões até construir um processo válido.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "process_builder",
  "title": "Atendimento",
  "blocks": [
    {
      "id": "start",
      "label": "Receber pedido",
      "kind": "start"
    },
    {
      "id": "check",
      "label": "Validar dados",
      "kind": "action"
    },
    {
      "id": "end",
      "label": "Finalizar",
      "kind": "end"
    }
  ],
  "requiredEdges": [
    [
      "start",
      "check"
    ],
    [
      "check",
      "end"
    ]
  ],
  "settings": {
    "allowExtraEdges": false
  }
}
```

Usar `activitytype="process_builder"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Renderizar blocos com IDs estáveis e conectores em SVG; permitir selecionar origem e destino para criar aresta.
2. Armazenar grafo de conexões em estrutura própria, sem confiar nas coordenadas de pixels para representar lógica.
3. Validar edges contra requiredEdges, proibir conexões a nós ausentes e ciclos se a atividade exigir DAG.
4. Para fluxos com condições, adicionar rótulos true/false nas arestas em vez de uma sequência única.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Conexões por IDs:** criar ligação escolhendo bloco origem e destino; não depender de proximidade visual para definir aresta lógica.
2. **Não duplicar aresta:** conectar A->B duas vezes não duplica ligação nem pontuação; permitir remover uma conexão individual sem destruir outras.
3. **Trocar destinos:** conexão existente pode ser substituída/removida antes de confirmar; não sobrescrever silenciosamente outro arco, sobretudo quando há saídas com rótulos sim/não.
4. **Validação de grafo:** rejeitar laço/ciclo quando modelo exige grafo acíclico, nós inexistentes, saídas sem destino e desconexões que violam regras de início/fim.
5. **Ordem visual não é lógica:** arrastar um bloco para reposicionar não altera conexões; zoom e layout responsivo apenas redesenham arestas.
6. **Diferentes fluxos válidos:** aceitar grafos equivalentes quando não existe ordem única, respeitando precedências/ramificações declaradas; comparar arestas e rótulos, não coordenadas.
7. **Alternativa teclado:** formulário acessível 'Conectar bloco X a bloco Y' e lista de ligações com Remover, junto ao fluxograma visual.
8. **Testes:** conexão duplicada, trocar destino, ciclo, dois ramos, zoom, bloco sem conexão e desfazer ligação.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Mostrar etapas corretas, conexões faltantes e razões do fluxo inválido.
- **Acessibilidade e mobile:** Fluxo alternativo em lista de ligações 'De' -> 'Para'; teclado e texto para descrever conexões.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Posições visuais opcionais, arestas construídas, blocos usados, tentativas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Ciclo, nó isolado, conexão duplicada, bifurcação, resize e acesso só por teclado.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade monte o processo para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/28-monte-o-processo.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

# 09. Classifique os itens

**Tipo proposto:** `categorize` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Classificação, comparação e construção de categorias.

Itens devem ser distribuídos em duas ou mais categorias. Cada item pode ter uma única categoria correta ou mais de uma, conforme regra declarada.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "categorize",
  "title": "Reciclagem",
  "categories": [
    {
      "id": "paper",
      "label": "Papel"
    },
    {
      "id": "metal",
      "label": "Metal"
    }
  ],
  "items": [
    {
      "id": "newspaper",
      "label": "Jornal",
      "categoryIds": [
        "paper"
      ]
    },
    {
      "id": "can",
      "label": "Lata",
      "categoryIds": [
        "metal"
      ]
    }
  ],
  "settings": {
    "allowMultiCategory": false
  }
}
```

Usar `activitytype="categorize"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Criar colunas de categorias e lista de itens disponíveis; validar IDs e categorias referenciadas.
2. Mover ou copiar itens conforme allowMultiCategory, com ações acessíveis por seleção e destino.
3. Se a atividade permite múltiplas categorias, armazenar conjunto de destinos por item; se não, apenas um.
4. Validar associações de conjuntos, tolerando ordem indiferente, e opcionalmente bloquear acertos.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e fazem parte dos critérios de aceitação. O gerador deve implementá-las e testá-las, não apenas mencioná-las na descrição do jogo.

1. **Mover sem duplicar:** em modo de categoria única, cada item pertence a zero ou uma categoria. Mudar de destino remove-o atomicamente da categoria anterior e o coloca na nova.
2. **Múltiplas categorias:** quando `allowMultiCategory=true`, o mesmo item pode ser associado a mais de uma categoria somente se o conteúdo declarar essa possibilidade; o conjunto não pode ter destinos duplicados.
3. **Desfazer/remover:** permitir retirar um item da categoria e devolvê-lo à lista de disponíveis enquanto a resposta não estiver definitiva.
4. **Capacidade e ordenação:** categorias aceitam múltiplos itens salvo limite explícito; deslocar item dentro da coluna não deve alterar sua classificação ou pontuar erro.
5. **Erro não destrutivo:** em modo de validação imediata, opção incorreta retorna item à posição anterior com explicação; em modo de correção final, manter posição provisória e marcar revisão sem travar.
6. **Interação alternativa:** arraste e operação selecionar item + selecionar categoria devem produzir o mesmo estado, inclusive no teclado.
7. **Testes:** item movido entre categorias, duas categorias corretas, desfazer, item repetido, destino vazio e arraste cancelado.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Explicação específica após classificar, acertos por categoria e itens pendentes.
- **Acessibilidade e mobile:** Botões de 'Selecionar categoria' e mover, etiquetas textuais, estados acessíveis e alvos grandes.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Alocações por item, erros e finalização.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Categoria vazia, item multicategoria, ID desconhecido, desfazer, reorganização responsiva e ordem de itens.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade classifique os itens para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/09-classifique-os-itens.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

# 05. Coloque na ordem correta

**Tipo proposto:** `sequence` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Processos, cronologia, algoritmos e ordenação lógica.

O aluno recebe cartões embaralhados e deve ordená-los corretamente. Pode representar etapas de uma experiência, acontecimentos históricos ou instruções.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "sequence",
  "title": "Lavar as mãos",
  "settings": {
    "allowRetry": true
  },
  "steps": [
    {
      "id": "wet",
      "label": "Molhar as mãos"
    },
    {
      "id": "soap",
      "label": "Aplicar sabão"
    },
    {
      "id": "rinse",
      "label": "Enxaguar"
    }
  ]
}
```

Usar `activitytype="sequence"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Manter a ordem canônica pela sequência de steps no JSON e gerar uma lista visual embaralhada de IDs.
2. Renderizar lista ordenável com controles de mover para cima/baixo; adicionar arraste por Pointer Events como atalho.
3. Após confirmação ou a cada movimento, comparar a permutação atual com a ordem canônica.
4. Em variantes de ordem parcial, modelar predecessores obrigatórios em vez de uma única sequência correta.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e fazem parte dos critérios de aceitação. O gerador deve implementá-las e testá-las, não apenas mencioná-las na descrição do jogo.

1. **Mover por inserção:** soltar cartão na posição N deve inserir ali o cartão selecionado, deslocando os demais uma posição, sem substituir ou apagar o cartão que ocupava o destino.
2. **Reordenar a qualquer momento:** item já posicionado pode ser movido novamente antes de finalizar; incluir opções de mover uma posição para cima/baixo e, quando útil, ir para posição escolhida.
3. **Soltar fora:** não alterar sequência nem contador; cancelar gesto elimina apenas destaque temporário de drop.
4. **Confirmação clara:** não concluir automaticamente ao coincidir com uma solução durante uma manipulação ainda em andamento; validar após confirmação ou no fim de uma ação estável, conforme opção explicitada na tela.
5. **Ordens equivalentes:** quando houver mais de uma sequência válida, listar alternativas aceitas ou dependências; não reprovar ordens que satisfazem precedências permitidas.
6. **Foco e persistência:** mover cartão não deve perder foco e a sequência deve ser armazenada por IDs estáveis, não por índices antigos.
7. **Testes:** mover primeira para última, reordenar item intermediário, cancelar arraste, repetir mesmo movimento, solução parcial válida e teclado somente.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Informar se a sequência está correta, apontar etapas a revisar apenas quando solicitado.
- **Acessibilidade e mobile:** Controles 'Mover para cima' e 'Mover para baixo', anúncio da nova posição e suporte integral a teclado.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Ordem atual dos IDs, tentativas e finalização.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Um único passo, IDs duplicados, inversão, etapas longas, mudança de tela e ordem alternativa permitida.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade coloque na ordem correta para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/05-coloque-na-ordem.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

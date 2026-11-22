# 26. Trilha do conhecimento

**Tipo proposto:** `knowledge_path` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Revisão com progressão por etapas e marcos.

O aluno avança por casas de um tabuleiro linear ou ramificado, enfrentando pequenos desafios em cada parada até alcançar o objetivo.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "knowledge_path",
  "title": "Ecologia",
  "nodes": [
    {
      "id": "start",
      "label": "Início",
      "next": [
        "n1"
      ]
    },
    {
      "id": "n1",
      "label": "Reciclagem",
      "question": {
        "prompt": "Papel pode ser reciclado?",
        "options": [
          "Sim",
          "Não"
        ],
        "correctIndex": 0
      },
      "next": [
        "finish"
      ]
    },
    {
      "id": "finish",
      "label": "Chegada",
      "next": []
    }
  ],
  "settings": {
    "rule": "advance_on_correct"
  }
}
```

Usar `activitytype="knowledge_path"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Validar grafo com nó inicial, chegada alcançável e referências next existentes; evitar ciclos infinitos por regra.
2. Renderizar tabuleiro em HTML/SVG com casas ativáveis somente se desbloqueadas.
3. Aplicar a regra de avanço após resolução da casa e registrar visita; se houver sorteio/dado, gerar e persistir o resultado.
4. Separar animação do tabuleiro da navegação lógica; limitar número de movimentos e provide reset.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Indicar casa atual, próxima meta e retorno sobre desafio antes de mover.
- **Acessibilidade e mobile:** Lista textual de casas com botões e status, não depender do desenho do tabuleiro.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Nó atual, casas concluídas, respostas, resultados de sorteio e finalização.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Referência inválida, casa sem saída, ciclo, voltar etapa, dado fora da faixa e perda de rede.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade trilha do conhecimento para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/26-trilha-do-conhecimento.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

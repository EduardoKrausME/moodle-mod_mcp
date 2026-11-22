# 15. Qual não pertence?

**Tipo proposto:** `odd_one_out` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Identificar propriedades, exceções e critérios de classificação.

Mostrar um grupo de itens e pedir para encontrar o único que não pertence segundo um critério declarado.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "odd_one_out",
  "title": "Animais",
  "rounds": [
    {
      "id": "mammals",
      "prompt": "Qual não é mamífero?",
      "items": [
        {
          "id": "dog",
          "label": "Cachorro"
        },
        {
          "id": "cat",
          "label": "Gato"
        },
        {
          "id": "eagle",
          "label": "Águia"
        },
        {
          "id": "whale",
          "label": "Baleia"
        }
      ],
      "oddId": "eagle",
      "explanation": "Águia é ave."
    }
  ]
}
```

Usar `activitytype="odd_one_out"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Exigir no mínimo três itens e oddId existente; garantir que o critério define uma resposta inequívoca.
2. Embaralhar visualmente as opções, mantendo correspondência dos IDs; usar botões ou cartões com imagem e texto.
3. Registrar uma única resposta por rodada, comparar com oddId e avançar após feedback.
4. Permitir rodada textual ou visual com a mesma lógica.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Explicar a propriedade compartilhada pelos demais itens e por que o selecionado é a exceção.
- **Acessibilidade e mobile:** Alternativas como botões rotulados, texto alternativo e ordem de foco estável.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Índice de rodada, escolhas por ID, ordem sorteada e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Resposta ausente, itens iguais, critério ambíguo, imagens não carregadas e seleção duplicada.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade qual não pertence? para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/15-qual-nao-pertence.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

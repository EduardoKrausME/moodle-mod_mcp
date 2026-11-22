# 21. Monte a frase

**Tipo proposto:** `sentence_order` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Sintaxe, idiomas e organização de ideias.

Palavras ou blocos de uma frase são embaralhados; o aluno os reorganiza para construir um enunciado correto.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "sentence_order",
  "title": "Português",
  "rounds": [
    {
      "id": "s1",
      "tokens": [
        {
          "id": "a",
          "text": "O"
        },
        {
          "id": "b",
          "text": "aluno"
        },
        {
          "id": "c",
          "text": "aprende."
        }
      ],
      "acceptedOrders": [
        [
          "a",
          "b",
          "c"
        ]
      ]
    }
  ]
}
```

Usar `activitytype="sentence_order"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Usar IDs por ocorrência para palavras repetidas; não comparar texto concatenado sem pontuação.
2. Renderizar chips acionáveis, permitindo tocar para inserir na frase ou reorganizar por mover esquerda/direita.
3. Validar permutação contra uma ou mais acceptedOrders explícitas; preservar alternativas gramaticais corretas.
4. Fornecer botão para remover da frase e devolver ao banco de palavras.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Mover sem duplicar:** selecionar uma palavra coloca sua ocorrência (ID) na frase e remove aquela ocorrência do banco; clicar novamente num chip da frase o devolve ao banco sem criar cópia.
2. **Palavras repetidas:** duas ocorrências de texto idêntico devem ter IDs independentes; a frase é validada pela ordem de IDs ou pelos grupos de equivalência explicitamente permitidos.
3. **Reordenar frase pronta:** arrastar um chip para posição ocupada insere-o e desloca os demais, sem substituir o chip de destino. Também fornecer botões Mover à esquerda/direita.
4. **Corrigir sem reiniciar:** aluno pode trocar ordem, remover e recolocar qualquer chip antes de confirmar; uma frase montada mas incorreta continua editável quando tentativas restantes permitirem.
5. **Pontuação e espaços:** compor apresentação com espaços e pontuação adequados; não comparar texto apenas por `join('')`, que poderia aceitar palavras coladas ou ignorar sinais.
6. **Respostas equivalentes:** permitir múltiplas ordens registradas em `acceptedOrders`; evitar reprovar frases gramaticalmente válidas por impor uma única sequência quando houver alternativas.
7. **Foco:** após inserir ou remover palavra, preservar o foco em controle equivalente; anunciar ordem atual sem roubar o foco do usuário.
8. **Testes:** palavra repetida, arrastar sobre chip ocupado, trocar primeira/última, remover, corrigir após erro, pontuação e teclado.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Mostrar frase formada e explicação de gramática ou sentido.
- **Acessibilidade e mobile:** Seleção e movimentação por teclado, leitura da frase atual e comandos de desfazer.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Ordem de tokens por rodada, erros e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Duas palavras iguais, pontuação, múltiplas ordens, tokens faltando e resposta parcial.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade monte a frase para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/21-monte-a-frase.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

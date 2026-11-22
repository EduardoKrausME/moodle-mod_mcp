# 08. Palavras cruzadas

**Tipo proposto:** `crossword` | **Dificuldade de implementação:** Médio

## Finalidade e experiência do aluno

Termos e definições com recuperação ativa.

O aluno preenche palavras entrecruzadas na horizontal e vertical a partir de pistas numeradas.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "crossword",
  "title": "Informática",
  "entries": [
    {
      "id": "cpu",
      "answer": "CPU",
      "clue": "Unidade central de processamento",
      "row": 0,
      "col": 0,
      "direction": "across"
    },
    {
      "id": "ram",
      "answer": "RAM",
      "clue": "Memória volátil",
      "row": 0,
      "col": 1,
      "direction": "down"
    }
  ]
}
```

Usar `activitytype="crossword"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Preferir grade já posicionada no JSON; validar cada entrada, limites, colisões, letras compatíveis nos cruzamentos e numeração.
2. Gerar células editáveis únicas e mapear cada célula às palavras que a atravessam; setas mudam foco, Backspace volta e Tab alterna pistas.
3. Destacar a palavra ativa sem ocultar outras; ao digitar em cruzamento, atualizar uma única célula compartilhada.
4. Verificar palavras por resposta normalizada, sem exigir validação global a cada tecla.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Verificar palavra ou jogo completo sob demanda; explicar a pista após resolução.
- **Acessibilidade e mobile:** Lista de pistas separada, navegação por teclado, rótulos linha/coluna e leitura das letras sem anunciar respostas.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Mapa célula->letra, palavras verificadas, dicas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Cruzamento incompatível, palavra fora da grade, Backspace, teclado móvel, foco e caracteres acentuados.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade palavras cruzadas para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/08-palavras-cruzadas.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

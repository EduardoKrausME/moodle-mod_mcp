# 07. Descubra a palavra

**Tipo proposto:** `word_guess` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Ortografia, terminologia e recuperação da memória.

O aluno recebe uma dica e lacunas correspondentes às letras de uma palavra; escolhe letras ou digita uma resposta completa antes de acabar as tentativas.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "word_guess",
  "title": "Ciências",
  "settings": {
    "maxErrors": 6,
    "allowFullAnswer": true
  },
  "rounds": [
    {
      "id": "p1",
      "answer": "FOTOSSINTESE",
      "hint": "Processo das plantas que utiliza luz"
    }
  ]
}
```

Usar `activitytype="word_guess"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Normalizar resposta e alfabeto de forma configurável; preservar acentos de exibição e tratar hífens/espaços como posições fixas.
2. Criar teclado virtual em botões e permitir teclado físico; armazenar letras já tentadas em conjunto.
3. Revelar todas as ocorrências da letra correta, contar erro só para letra inédita e finalizar por acerto ou limite.
4. Caso use várias rodadas, manter índice da rodada atual e pontuação apenas lúdica.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e fazem parte dos critérios de aceitação. O gerador deve implementá-las e testá-las, não apenas mencioná-las na descrição do jogo.

1. **Letra tentada uma só vez:** escolher novamente uma letra já tentada não consome erro adicional, não avança rodada e não altera placar; indicar visualmente que já foi utilizada.
2. **Todas as ocorrências:** ao acertar uma letra, revelar todas as posições correspondentes, sem contabilizar múltiplos acertos independentes.
3. **Maiúsculas, acentos e sinais:** normalizar comparação conforme idioma, preservando apresentação original. Espaços, hífens e pontuação fixos não devem pedir tentativas.
4. **Palavra inteira:** no modo de resposta completa, comparar com palavra e sinônimos permitidos, aplicando regra declarada de erro por palpite errado; não misturar palpite de palavra com tecla isolada.
5. **Limites:** não aceitar novas tentativas depois de adivinhar ou atingir `maxErrors`; mostrar explicação e solução no encerramento, e permitir reiniciar outra rodada separadamente.
6. **Entrada física/virtual:** teclas rápidas não podem registrar o mesmo caractere duas vezes; campo de resposta completa deve permitir edição normal, Backspace e colagem.
7. **Testes:** palavra com letras repetidas, acentos, separadores, letras já escolhidas, tentativa final correta e digitação por teclado móvel.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Dica progressiva, contador de erros e explicação da palavra ao concluir.
- **Acessibilidade e mobile:** Input alternativo de resposta completa, leitores de tela anunciam acertos e erros; nada de animação de enforcamento.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Rodada, letras tentadas, erros, dicas e palavras resolvidas.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Letras repetidas, acentos, espaços, tentativa repetida, palavra inválida e fim por limite.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade descubra a palavra para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/07-descubra-a-palavra.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

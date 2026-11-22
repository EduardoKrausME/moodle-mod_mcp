# 12. Verdadeiro ou falso rápido

**Tipo proposto:** `true_false` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Checagem formativa e correção de concepções equivocadas.

Exibir uma afirmação de cada vez; o aluno escolhe verdadeiro ou falso, vê o comentário e avança.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "true_false",
  "title": "Segurança digital",
  "statements": [
    {
      "id": "pwd",
      "text": "Uma senha pode ser compartilhada com colegas.",
      "correct": false,
      "explanation": "Senhas são pessoais."
    },
    {
      "id": "backup",
      "text": "Backups ajudam a recuperar dados.",
      "correct": true,
      "explanation": "A cópia permite recuperação."
    }
  ],
  "settings": {
    "shuffle": false
  }
}
```

Usar `activitytype="true_false"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Validar booleanos reais e explicações obrigatórias; determinar ordem antes de iniciar.
2. Manter estado de questão atual, resposta escolhida e trava contra envio duplicado.
3. Comparar resposta com correct e mostrar feedback; avançar somente após confirmar leitura ou usar botão Próximo.
4. Calcular desempenho formativo local; não confundir com nota oficial.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e fazem parte dos critérios de aceitação. O gerador deve implementá-las e testá-las, não apenas mencioná-las na descrição do jogo.

1. **Resposta única por rodada:** ao escolher Verdadeiro ou Falso, registrar uma única resposta; clique duplo não pontua duas vezes nem pula questão.
2. **Feedback antes de avançar:** apresentar explicação e botão Próximo; não desaparecer com a questão antes que o aluno possa ler ou ouvir o feedback.
3. **Correção antes da confirmação:** se a atividade oferecer botão Confirmar, permitir mudar escolha até confirmar. Depois disso, bloquear alteração da resposta registrada, salvo revisão explicitamente habilitada.
4. **Voltar é revisão:** navegar para questão anterior deve mostrar a resposta salva e sua explicação, sem duplicar tentativas ou pontuação.
5. **Limites de tempo:** não impor tempo em atividade simples; se houver modo cronometrado, oferecer alternativa sem tempo para acessibilidade e declarar o comportamento de expiração.
6. **Testes:** toque repetido, troca de seleção antes de confirmar, Voltar/Próximo, último item, anúncio do feedback e salvar antes da transição.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Explicar por que a afirmação é verdadeira ou falsa, não apenas exibir cor verde/vermelha.
- **Acessibilidade e mobile:** Botões Verdadeiro/Falso, foco no feedback e leitura por aria-live, sem limite de tempo obrigatório.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Índice, respostas por ID, ordem, tentativas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Booleano vindo como string, clique duplo, afirmação longa, retorno à anterior e uso offline.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade verdadeiro ou falso rápido para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/12-verdadeiro-ou-falso.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

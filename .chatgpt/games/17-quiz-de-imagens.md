# 17. Quiz de imagens

**Tipo proposto:** `image_quiz` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Reconhecimento visual e associação conceito-imagem.

Apresentar uma imagem e perguntar o que ela representa; o aluno escolhe a resposta dentre alternativas ou seleciona a imagem que corresponde à pergunta.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "image_quiz",
  "title": "Ferramentas",
  "questions": [
    {
      "id": "tool1",
      "image": {
        "src": "assets/martelo.png",
        "alt": "Ferramenta de metal com cabo"
      },
      "prompt": "Que ferramenta é esta?",
      "options": [
        {
          "id": "hammer",
          "label": "Martelo"
        },
        {
          "id": "wrench",
          "label": "Chave inglesa"
        }
      ],
      "correctId": "hammer",
      "explanation": "Serve para golpear pregos."
    }
  ]
}
```

Usar `activitytype="image_quiz"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Renderizar imagem com dimensões estáveis e lazy-load moderado, acompanhada de pergunta e opções.
2. Embaralhar opções por ID, preservar a mesma ordem ao retomar e impedir respostas múltiplas involuntárias.
3. Comparar correctId somente para feedback lúdico; para nota confiável integrar mecanismo de avaliação validado no servidor.
4. Permitir modo inverso: pergunta textual e várias imagens, sem alterar formato de resposta.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Fornecer explicação com imagem destacada e possibilidade de revisar o conteúdo.
- **Acessibilidade e mobile:** Alternativas com rótulos textuais e alt adequado; não usar apenas 'imagem A/B' sem descrição significativa.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Respostas e ordem de opções por questão, índice, feedback e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Imagem faltante, resposta inválida, ordem sorteada, duas imagens muito parecidas e layout mobile.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade quiz de imagens para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/17-quiz-de-imagens.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

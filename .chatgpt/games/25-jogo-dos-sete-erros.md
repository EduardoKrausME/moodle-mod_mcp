# 25. Jogo dos sete erros

**Tipo proposto:** `seven_errors` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Inspeção visual de falhas e conformidade de procedimentos.

Uma cena de equipamento, laboratório ou ambiente de trabalho contém exatamente sete erros pedagógicos; o aluno deve localizar todos, marcando as regiões e recebendo explicação.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "seven_errors",
  "title": "Laboratório seguro",
  "image": {
    "src": "assets/lab-inseguro.png",
    "alt": "Cena de laboratório com situações inseguras"
  },
  "errors": [
    {
      "id": "e1",
      "x": 0.25,
      "y": 0.4,
      "radius": 0.06,
      "label": "Sem óculos de proteção"
    },
    {
      "id": "e2",
      "x": 0.7,
      "y": 0.6,
      "radius": 0.08,
      "label": "Líquido aberto"
    }
  ],
  "settings": {
    "expectedCount": 7
  }
}
```

Usar `activitytype="seven_errors"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Especializar o motor de hotspots: cada erro tem ID, coordenada normalizada, região e explicação.
2. No conteúdo de produção, exigir exatamente sete erros únicos (o exemplo é deliberadamente reduzido); recusar publicar quando a lista não corresponder a expectedCount.
3. Calcular toque relativo à imagem, identificar região e marcar apenas uma vez; oferecer zoom e lista de erros descobertos.
4. Diferenciar de 'encontre as diferenças': aqui pode haver apenas uma imagem com situações incorretas.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Lista dos erros encontrados, justificativa técnica e indicador 'x de 7'.
- **Acessibilidade e mobile:** Modo alternativo por setores enumerados e descrição detalhada da cena para não excluir alunos sem visão.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** IDs dos sete erros identificados, dicas, tentativas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Menos/mais de sete, coordenadas inválidas, duas regiões sobrepostas, zoom e toque repetido.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade jogo dos sete erros para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/25-jogo-dos-sete-erros.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

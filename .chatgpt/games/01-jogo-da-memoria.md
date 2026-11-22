# 01. Jogo da memória

**Tipo proposto:** `memory` | **Dificuldade de implementação:** Muito fácil

## Finalidade e experiência do aluno

Memorização, reconhecimento e associação de conceitos.

Exibir cartas viradas para baixo. O aluno vira duas; se o par corresponde, permanecem abertas; caso contrário voltam a fechar após um intervalo. Os pares podem ser imagem/imagem, palavra/palavra ou imagem/definição, sem obrigar conteúdos idênticos.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "memory",
  "title": "Órgãos do corpo",
  "settings": {
    "shuffle": true,
    "maxAttempts": 0
  },
  "pairs": [
    {
      "id": "heart",
      "a": {
        "kind": "image",
        "src": "assets/coracao.png",
        "alt": "Ilustração de um coração"
      },
      "b": {
        "kind": "text",
        "text": "Coração"
      }
    },
    {
      "id": "lung",
      "a": {
        "kind": "image",
        "src": "assets/pulmoes.png",
        "alt": "Ilustração de pulmões"
      },
      "b": {
        "kind": "text",
        "text": "Pulmões"
      }
    }
  ]
}
```

Usar `activitytype="memory"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Validar IDs únicos e exatamente duas faces por par; converter cada par em duas cartas com o mesmo pairId e IDs de carta diferentes.
2. Embaralhar com Fisher-Yates uma vez por sessão, preservando a ordem no estado salvo. Renderizar grade responsiva com botões reais.
3. Permitir no máximo duas cartas abertas, bloquear entradas somente durante a comparação e ignorar toque duplicado na mesma carta.
4. Comparar pairId, registrar tentativas, manter pares encontrados expostos e encerrar quando todos estiverem completos.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Acerto com animação discreta e anúncio textual; erro sem punição obrigatória; mostrar tentativas e pares restantes.
- **Acessibilidade e mobile:** Enter/Espaço viram a carta, foco permanece previsível, faces fechadas têm rótulo acessível sem revelar resposta; texto alternativo nas imagens.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Ordem dos IDs, IDs dos pares concluídos, cartas abertas, tentativas e finalização; ao retomar, fechar cartas temporárias.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Duas cartas do mesmo par, seleção duplicada, clique rápido, número ímpar inválido, retomada e imagens ausentes.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade jogo da memória para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/01-jogo-da-memoria.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

# 24. Dominó educativo

**Tipo proposto:** `domino` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Associação e construção de sequências de conceitos.

O aluno conecta peças de duas metades de modo que a ponta livre combine com o conceito, imagem ou definição da próxima peça.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "domino",
  "title": "Conceitos de TI",
  "tiles": [
    {
      "id": "t1",
      "left": {
        "id": "cpu",
        "label": "CPU"
      },
      "right": {
        "id": "memory",
        "label": "Memória"
      }
    },
    {
      "id": "t2",
      "left": {
        "id": "memory",
        "label": "Memória"
      },
      "right": {
        "id": "disk",
        "label": "Disco"
      }
    }
  ],
  "settings": {
    "allowFlip": true
  }
}
```

Usar `activitytype="domino"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Modelar cada peça como duas extremidades com ID semântico, não confiar em texto idêntico.
2. Escolher peça inicial, embaralhar as restantes e validar encaixe entre extremidade livre e ponta da nova peça.
3. Permitir virar peça quando configurado, reordenar ou retirar última peça, garantindo que nenhuma seja usada duas vezes.
4. Para dominó não linear, declarar grafo de encaixes válidos e não assumir apenas uma solução.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Anunciar encaixe correto e mostrar cadeia já montada.
- **Acessibilidade e mobile:** Selecionar peça, escolher extremidade e orientação por controles, sem exigir precisão de arrastar.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Peças utilizadas, orientação, ordem e ponta ativa.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Ciclo, peça duplicada, combinação ambígua, virar repetidamente e mesa estreita.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade dominó educativo para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/24-domino-educativo.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

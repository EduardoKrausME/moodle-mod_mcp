# 10. Identifique na imagem

**Tipo proposto:** `image_hotspots` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Anatomia, geografia e identificação de componentes.

Uma imagem apresenta pontos ou áreas onde o aluno deve posicionar o rótulo correto, ou tocar na região correspondente ao termo solicitado.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "image_hotspots",
  "title": "Partes do computador",
  "image": {
    "src": "assets/placa.png",
    "alt": "Placa-mãe vista de cima"
  },
  "hotspots": [
    {
      "id": "socket",
      "x": 0.43,
      "y": 0.32,
      "label": "Soquete do processador"
    },
    {
      "id": "ram",
      "x": 0.7,
      "y": 0.2,
      "label": "Slots de RAM"
    }
  ],
  "settings": {
    "mode": "label"
  }
}
```

Usar `activitytype="image_hotspots"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Usar hotspots com coordenadas normalizadas e validação de 0 a 1; calcular posição a partir da área útil da imagem.
2. Renderizar pinos em layer sobre a imagem e etiquetas em lista lateral; modo alternativo pergunta onde está uma peça.
3. Usar associação por IDs para checar o rótulo escolhido; nunca usar apenas comparação textual por proximidade.
4. Evitar sobreposição de alvos e oferecer zoom com preservação do mapeamento.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Destacar região correta, fornecer pequena explicação e progresso por rótulos.
- **Acessibilidade e mobile:** Lista de pontos numerados permite responder sem precisão de clique; texto alternativo geral e descrição de cada região.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Mapa hotspot->label resolvido, tentativas, índice e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Imagem responsiva, zoom, object-fit, sobreposição de pontos, teclado, posicionamento fora de 0..1.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade identifique na imagem para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/10-identifique-na-imagem.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

# 19. Imagem misteriosa

**Tipo proposto:** `reveal_image` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Inferência visual e reconhecimento gradual.

Uma imagem começa oculta por mosaicos ou desfoque; a cada dica solicitada ou resposta, mais partes são reveladas até que o aluno identifique o conteúdo.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "reveal_image",
  "title": "Geografia",
  "image": {
    "src": "assets/continente.png",
    "alt": "Mapa a identificar"
  },
  "answer": "África",
  "aliases": [
    "continente africano"
  ],
  "settings": {
    "tiles": 12,
    "revealPerHint": 2,
    "maxGuesses": 4
  }
}
```

Usar `activitytype="reveal_image"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Criar uma malha de blocos overlay sobre imagem estática; preservar aspect ratio e manter o contorno do tabuleiro.
2. Gerar ordem determinística de revelação, permitindo pistas visuais sem precisar editar o arquivo original.
3. Comparar tentativa de resposta com answer e aliases normalizados; revelar blocos no máximo definido.
4. Permitir alternativa sem imagem com dicas descritivas equivalentes.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Revelação determinística:** embaralhar e salvar a sequência dos blocos no começo; novo render ou retorno à atividade não altera quais partes já foram descobertas.
2. **Uma ação, uma revelação:** cada acionamento de Revelar mais abre no máximo `revealPerHint` blocos ainda ocultos; ignorar cliques repetidos enquanto atualização está pendente.
3. **Limites:** não abrir mais blocos que os restantes; após imagem totalmente visível, desativar botão de revelar e informar se ainda há tentativa de adivinhação.
4. **Tentativas de resposta:** comparar resposta completa com `answer` e `aliases`; envio vazio/repetido não pode consumir tentativas adicionais sem informação ao aluno.
5. **Encerrar cedo:** se o aluno acertar antes da revelação completa, concluir rodada sem forçar abrir todas as peças primeiro; a imagem completa pode ser mostrada somente após conclusão.
6. **Acessibilidade:** fornecer dicas descritivas progressivas e opção textual equivalente; desfoque e mosaico não devem ser a única forma de resolver.
7. **Testes:** última peça parcial, `revealPerHint` maior que restantes, clique duplo, zoom/resize, resposta antecipada e retomar com blocos abertos.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Mostrar quantas peças restam, dica textual opcional e identificação completa ao acertar ou desistir.
- **Acessibilidade e mobile:** Campo de resposta, botão 'Revelar mais' e descrição alternativa; não usar blur como único meio de acesso.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Ordem/revelados, tentativas, resposta e estado final.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Aspect ratio, 0 blocos, revelação completa, nomes alternativos, imagem indisponível e conexão lenta.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade imagem misteriosa para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/19-imagem-misteriosa.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

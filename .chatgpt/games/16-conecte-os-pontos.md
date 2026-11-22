# 16. Conecte os pontos

**Tipo proposto:** `connect_dots` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Sequências, coordenadas e reconhecimento de padrões.

O aluno liga pontos numerados ou identificados em uma ordem correta, formando uma figura, rota ou fluxo.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "connect_dots",
  "title": "Desenhe a molécula",
  "points": [
    {
      "id": "p1",
      "x": 0.15,
      "y": 0.3,
      "label": "1"
    },
    {
      "id": "p2",
      "x": 0.5,
      "y": 0.15,
      "label": "2"
    },
    {
      "id": "p3",
      "x": 0.85,
      "y": 0.3,
      "label": "3"
    }
  ],
  "path": [
    "p1",
    "p2",
    "p3"
  ],
  "settings": {
    "allowBacktrack": true
  }
}
```

Usar `activitytype="connect_dots"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Usar SVG escalável para pontos e linhas; coordenadas normalizadas e viewBox definido.
2. Manter próximo ponto esperado conforme path e desenhar segmento somente se o ponto correto for ativado.
3. Não depender de colisão por pixel: cliques e teclado ativam IDs, depois desenham segmento do anterior ao atual.
4. Se houver várias rotas, representar paths aceitos em vez de apenas uma sequência.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Próximo ponto esperado:** somente o ID seguinte na sequência `path` pode criar um novo segmento; clicar em outro ponto apresenta aviso, sem desenhar linha ou perder trechos corretos.
2. **Conexão progressiva:** ao selecionar o ponto correto, adicionar uma aresta entre último ponto confirmado e o novo; para o primeiro ponto, apenas selecionar início, sem criar aresta de origem desconhecida.
3. **Desfazer último segmento:** retirar somente a conexão mais recente e voltar o passo corrente, sem apagar toda a figura. Em múltiplos caminhos permitidos, desfazer também restaura a ramificação anterior.
4. **Sem duplicidade:** tocar no ponto já confirmado ou clicar várias vezes durante animação não adiciona segmentos repetidos nem altera pontuação.
5. **Interação responsiva:** mapear os pontos com SVG/viewBox e IDs, não por coordenadas absolutas da janela; zoom e resize não alteram o trajeto salvo.
6. **Acesso sem precisão de toque:** fornecer lista de botões dos pontos com nomes/ordem, ativáveis pelo teclado; o resultado deve ser igual ao toque na figura.
7. **Testes:** início, ponto fora da ordem, último ponto, toque repetido, desfazer, pausa em animação e ampliação da tela.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Mostrar segmento construído, progresso e figura final com explicação.
- **Acessibilidade e mobile:** Lista textual com pontos acionáveis, setas de navegação e leitura do passo corrente.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** IDs do percurso confirmado, erros, dicas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Coordenadas inválidas, ponto repetido, clique fora do SVG, zoom, backtrack e teclado.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade conecte os pontos para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/16-conecte-os-pontos.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

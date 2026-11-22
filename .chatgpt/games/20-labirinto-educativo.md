# 20. Labirinto educativo

**Tipo proposto:** `maze` | **Dificuldade de implementação:** Médio

## Finalidade e experiência do aluno

Resolução de problemas, caminhos e tomada de decisão.

O aluno percorre um labirinto em grade ou grafo; algumas portas exigem responder a perguntas ou tomar decisões para liberar a passagem.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "maze",
  "title": "Energia",
  "grid": [
    "S..#",
    ".#..",
    "...E"
  ],
  "challenges": [
    {
      "id": "gate1",
      "row": 1,
      "col": 2,
      "prompt": "Energia solar é renovável?",
      "options": [
        "Sim",
        "Não"
      ],
      "correctIndex": 0
    }
  ],
  "settings": {
    "moves": [
      "up",
      "down",
      "left",
      "right"
    ]
  }
}
```

Usar `activitytype="maze"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Validar grid retangular com início S, saída E, paredes # e células transitáveis; testar solvabilidade por BFS.
2. Representar jogador por coordenada discreta e executar movimento apenas em casas vizinhas livres.
3. Se uma casa possui desafio, pausar movimento, mostrar modal acessível e só liberar mediante regra definida.
4. Manter câmera, animação e lógica separadas; nunca inferir vitória por simples proximidade visual.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Movimento por célula:** cada comando move exatamente uma célula ortogonal permitida; parede, borda ou diagonal são bloqueadas sem deslocar o jogador nem contabilizar avanço válido.
2. **Uma tecla por passo:** para teclado e controles touch, definir repetição com intervalo controlado ou um passo por acionamento; ignorar comandos sobrepostos enquanto uma transição está em curso.
3. **Portas e desafios:** ao entrar em casa com desafio não resolvido, interromper progressão e exibir pergunta; só marcar a passagem como liberada após a condição definida, com feedback sobre tentativa errada.
4. **Volta após erro:** erro na pergunta não pode teleportar o aluno ou mover para dentro da parede; permanecer na casa de origem ou no portal bloqueado conforme regra explícita e consistente.
5. **Persistir estado discreto:** registrar posição, desafios concluídos e portas liberadas; ao retomar, validar posição transitável e não repetir recompensa de desafio já resolvido.
6. **Mapa solvável:** validar se início e fim são alcançáveis respeitando as portas e respostas; não gerar labirinto sem saída obrigatória.
7. **Comandos acessíveis:** setas e botões direcionais nomeados; controles não devem capturar setas quando foco estiver em campos de resposta de um desafio.
8. **Testes:** parede, limite, pressionar direções rapidamente, pergunta errada, portal liberado, retorno de sessão e celular em paisagem.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Retorno da questão contextualizado, indicação de caminho percorrido e objetivos restantes.
- **Acessibilidade e mobile:** Botões direcionais, setas no teclado e alternativa textual de salas/conexões.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Posição, portas liberadas, desafios respondidos, movimentos e finalização.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Labirinto sem saída, duas saídas, desafio bloqueado, movimento diagonal proibido e mobile.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade labirinto educativo para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/20-labirinto-educativo.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

# 04. Quebra-cabeça de imagens

**Tipo proposto:** `jigsaw` | **Dificuldade de implementação:** Fácil em grade; médio com formas irregulares

## Finalidade e experiência do aluno

Observação, percepção espacial e reconstrução visual.

Uma imagem é dividida em peças embaralhadas; o aluno as reposiciona até reconstruir a figura. A primeira versão deve usar grade retangular e troca entre posições.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "jigsaw",
  "title": "Estrutura da célula",
  "image": {
    "src": "assets/celula.png",
    "alt": "Desenho de uma célula",
    "width": 1200,
    "height": 900
  },
  "settings": {
    "rows": 3,
    "cols": 3,
    "mode": "swap",
    "preview": true
  }
}
```

Usar `activitytype="jigsaw"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Carregar e validar a imagem; calcular retângulos de recorte por linha/coluna; usar CSS background-position/background-size ou canvas sem transmitir bitmap desnecessariamente.
2. Atribuir a cada peça um índice correto e embaralhar apenas os índices da montagem; evitar embaralhamento já resolvido.
3. Permitir selecionar duas peças para trocar, ou arrastar uma até outra; comparar índice de peça e índice de casa após cada jogada.
4. Oferecer referência da imagem completa e implementar um modo futuro de peças irregulares separado do motor de grade.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e fazem parte dos critérios de aceitação. O gerador deve implementá-las e testá-las, não apenas mencioná-las na descrição do jogo.

1. **Interação por troca:** em modo `swap`, selecionar peça A e peça B troca posições, mesmo que ambas estejam ocupadas; não apagar, clonar nem deslocar peças para fora da grade.
2. **Modo de peça fixa:** fixar peças já corretas somente se uma configuração explícita habilitar o bloqueio. Peças fixadas devem comunicar seu estado e não aceitar troca; por padrão, permitir mover qualquer peça enquanto não concluir.
3. **Arraste sem perda:** liberar ponteiro fora de peça válida ou receber `pointercancel` desfaz a seleção transitória e deixa o tabuleiro como estava.
4. **Fonte única:** manter permutação dos IDs de peças como estado da partida; nunca inferir a posição lógica pelo `transform` CSS ou pelas coordenadas visuais.
5. **Imagem proporcional:** manter tamanho e recortes corretos em orientação retrato/paisagem e com zoom; não redefinir a permutação no `resize`.
6. **Conclusão e reinício:** verificar todos os índices após cada troca; ao concluir, impedir nova movimentação acidental, mostrar imagem montada e oferecer Reiniciar com confirmação se necessário.
7. **Alternativa teclado:** selecionar duas peças por Enter/Espaço ou abrir controle 'Trocar com posição N'; mover foco para posição previsível após troca.
8. **Testes:** troca entre peças ocupadas, peça fixa, toque fora, tabuleiro já resolvido ao embaralhar, rotacionar celular e restaurar permutação.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Contagem de movimentos e indicação de conclusão; opcionalmente mostrar imagem-guia sem revelar a solução automaticamente.
- **Acessibilidade e mobile:** Cada peça é botão com posição atual, número e comando de troca por teclado; alternativa com menu 'mover para posição'.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Permutação da grade, movimentos, estado concluído; preservar proporção da imagem no celular.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Imagem com erro de carregamento, dimensões ímpares, peça já correta, arraste cancelado, 4/9/16/25 peças.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade quebra-cabeça de imagens para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/04-quebra-cabeca.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

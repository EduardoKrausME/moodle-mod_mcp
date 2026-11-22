# 02. Caça-palavras

**Tipo proposto:** `word_search` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Vocabulário, reconhecimento ortográfico e revisão de termos.

O aluno encontra palavras escondidas em uma matriz de letras selecionando o primeiro e o último caractere ou arrastando o dedo sobre uma linha contínua. Palavras podem aparecer na horizontal, vertical e diagonal.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "word_search",
  "title": "Biologia",
  "settings": {
    "rows": 10,
    "cols": 10,
    "directions": [
      "E",
      "S",
      "SE"
    ],
    "showHints": true
  },
  "words": [
    {
      "id": "cell",
      "word": "CELULA",
      "hint": "Unidade básica da vida"
    },
    {
      "id": "dna",
      "word": "DNA",
      "hint": "Material genético"
    }
  ]
}
```

Usar `activitytype="word_search"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Normalizar palavras com regra explícita para acentos (exibir original na lista e usar letras normalizadas no tabuleiro).
2. Gerar grade com posicionamento por tentativa e backtracking limitado; testar colisões, limites e sobreposições apenas quando letras coincidirem.
3. Preencher espaços vazios com letras aleatórias; persistir grade e posições para a sessão não mudar ao retomar.
4. Traduzir gesto de início/fim em sequência de células; validar alinhamento e comparar palavra e orientação reversa quando habilitada.
5. **Grade responsiva que ocupa a área útil:** a matriz deve preencher horizontalmente a largura disponível do contêiner/iframe, sem ficar limitada a uma grade pequena fixa (como 8×8) ou concentrada em um canto. Calcular colunas pela largura real e pelo tamanho mínimo legível/tocável das células: **mais colunas no desktop, menos no celular**; ampliar linhas para aproveitar a altura visível quando couber. Usar CSS Grid com colunas fluidas, células quadradas, quebra por viewport e sem rolagem horizontal desnecessária. Não simplesmente esticar oito letras até ficarem enormes.
6. **Redimensionamento sem perder progresso:** ao mudar largura, orientação ou dimensões do iframe, adaptar a quantidade de colunas/linhas, reposicionar todas as palavras com validação de encaixe e atualizar as coordenadas e rótulos; preservar IDs das palavras encontradas e suas marcações. Nunca cortar uma palavra, esconder um trecho do tabuleiro, aceitar um acerto com posições antigas nem regenerar continuamente a cada movimento do ponteiro.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e fazem parte dos critérios de aceitação. O gerador deve implementá-las e testá-las, não apenas mencioná-las na descrição do jogo.

1. **Trajeto contínuo:** ao arrastar, selecionar apenas células alinhadas horizontalmente, verticalmente ou diagonalmente de acordo com `directions`; não aceitar zigue-zague nem saltos entre células.
2. **Início/fim e cancelamento:** usar Pointer Events com `setPointerCapture` quando aplicável, converter movimento em células e finalizar no `pointerup`; em `pointercancel`/saída interrompida, limpar somente a seleção provisória.
3. **Ordem reversa explícita:** aceitar a palavra ao contrário apenas se a configuração permitir; verificar a sequência e as coordenadas contra as posições efetivamente geradas, não somente contra texto coincidente ao acaso.
4. **Palavras já encontradas:** manter seleção e destaque ao iniciar novo gesto, nunca somar a mesma palavra duas vezes; permitir sobreposição em letras comuns sem apagar destaques anteriores.
5. **Toque e teclado com instruções contextuais:** oferecer modo "primeira letra + última letra" com a mesma validação do arraste. Antes de começar, mostrar "Clique na primeira letra da palavra ou arraste pelas letras."; imediatamente após o primeiro clique/toque/Enter/Espaço, mudar para **"Clique agora na última letra da palavra."**, com mensagem acessível `aria-live`. Setas navegam, Enter/Espaço confirmam pontos e Escape ou botão Cancelar limpa apenas a seleção provisória. Após selecionar a última letra, voltar à orientação inicial. Não duplicar seleção ou pontuação quando o navegador gerar `pointerup` e `click` no mesmo gesto.
6. **Feedback ao soltar:** o aluno também pode iniciar na primeira letra e **arrastar o mouse ou dedo até a última**, com prévia do trecho selecionado; soltar conclui e valida a palavra. Um toque simples deve marcar somente o início, sem exigir arrastar. Seleção inválida limpa seu realce transitório sem apagar progresso; seleção correta permanece marcada e atualiza lista, contador e estado salvo. Cancelamento do gesto não equivale a erro.
7. **Teste de borda:** gesto diagonal, dedo solto fora, duas palavras sobrepostas, seleção reversa habilitada/desabilitada, toque em palavra pronta e rolagem em telas pequenas.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Realçar palavras corretas na grade e na lista; dicas opcionais e indicação de palavras restantes.
- **Acessibilidade e mobile:** Oferecer seleção por teclado de célula inicial e final, além de lista textual de pistas; células com rótulos de linha/coluna.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Matriz, posições das palavras, seleção corrente descartável e IDs encontrados; evitar regeneração ao carregar.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Palavra maior que a grade, colisões impossíveis, diagonal, acentos, tela pequena e seleção invertida. Validar desktop largo, tablet, celular estreito, orientação alterada, largura do iframe diferente da janela, primeira letra seguida de instrução contextual, segundo clique, clique repetido, arraste com mouse e dedo, gesto cancelado, saída do tabuleiro, foco por teclado, letras sobrepostas e manutenção dos acertos após redimensionar.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade caça-palavras para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/02-caca-palavras.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

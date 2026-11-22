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
- **Testes obrigatórios:** Palavra maior que a grade, colisões impossíveis, diagonal, acentos, tela pequena e seleção invertida.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade caça-palavras para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/02-caca-palavras.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

# 13. Complete as lacunas

**Tipo proposto:** `fill_blanks` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Contextualizar vocabulário e compreender relações dentro de textos.

Um texto contém lacunas; o aluno preenche digitando ou selecionando termos de um banco de palavras.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "fill_blanks",
  "title": "Biologia",
  "segments": [
    {
      "text": "O "
    },
    {
      "blankId": "b1"
    },
    {
      "text": " transporta oxigênio."
    }
  ],
  "blanks": [
    {
      "id": "b1",
      "answers": [
        "sangue"
      ],
      "hint": "Tecido líquido"
    }
  ],
  "settings": {
    "mode": "select",
    "wordBank": [
      "sangue",
      "pulmão"
    ]
  }
}
```

Usar `activitytype="fill_blanks"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Representar texto como segmentos estruturados, nunca substituir tokens livres em HTML fornecido por IA.
2. Criar controles de input/select em cada lacuna com label e ID próprio; a opção drag é apenas uma interface adicional.
3. Comparar respostas normalizadas (espaço, caixa e acento conforme configuração), permitindo sinônimos explicitamente listados.
4. Rejeitar lacunas desconhecidas, opções inexistentes e respostas vazias quando obrigatórias.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Verificar por lacuna ou todas, sugerir revisão com dica em vez de preencher automaticamente.
- **Acessibilidade e mobile:** Labels contextuais com frase anterior e posterior; seleção por teclado sem depender de arraste.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** Respostas por blankId, lacunas validadas, dicas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Lacunas repetidas, pontuação, sinônimos, HTML perigoso e palavras faltantes no banco.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade complete as lacunas para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/13-complete-as-lacunas.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

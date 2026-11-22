# 30. Acerte o alvo

**Tipo proposto:** `target_click` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Reconhecimento rápido, atenção seletiva e categorização.

Alvos surgem na tela e o aluno deve tocar somente os que atendem a uma condição, como números pares, símbolos seguros ou palavras da categoria solicitada.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "target_click",
  "title": "Números pares",
  "prompt": "Selecione apenas números pares",
  "targets": [
    {
      "id": "n2",
      "label": "2",
      "valid": true
    },
    {
      "id": "n3",
      "label": "3",
      "valid": false
    },
    {
      "id": "n4",
      "label": "4",
      "valid": true
    }
  ],
  "settings": {
    "moving": false,
    "maxMistakes": 0
  }
}
```

Usar `activitytype="target_click"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Exibir alvos como botões em uma grade estável na primeira versão; movimento animado é opcional e não altera a lógica de validação.
2. Validar IDs únicos, marcação booleana valid e tamanho mínimo de área de toque.
3. Registrar cada alvo apenas uma vez, acerto/erro de acordo com valid e completar quando todos os válidos forem selecionados.
4. Em modo animado, limitar velocidade, evitar alvos fora da viewport e oferecer modo parado equivalente.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e devem ser implementadas e testadas pelo código gerado, não apenas descritas.

1. **Um alvo, uma interação:** primeiro toque/tecla em ID ativo registra acerto ou erro e desativa aquele alvo; eventos repetidos não somam pontos ou tentativas.
2. **Critério explícito:** alvos válidos são os identificados por `valid`; ao concluir, exigir seleção de todos os válidos e aplicar limite de erros apenas se configurado.
3. **Mover ou não mover:** modo estático é o padrão; em modo animado, movimento não pode mudar IDs ou permitir que alvo saia da área visível.
4. **Pausa e redução de movimento:** permitir pausar/reiniciar animação, respeitar `prefers-reduced-motion` e oferecer modo estático equivalente com mesma verificação.
5. **Toque responsivo:** usar botões de tamanho apropriado, z-index previsível e prevenção contra sobreposição; toque fora não conta erro se nenhum alvo foi ativado.
6. **Sem velocidade obrigatória:** cronômetro opcional não pode ser única forma de vencer quando houver necessidade de acessibilidade.
7. **Testes:** toque duplo, alvo em movimento, alvo sobreposto, pausa, orientação mobile, final com todos os válidos e modo teclado.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Destacar alvos acertados, explicar critério e apresentar total de erros sem penalidade injusta por toque.
- **Acessibilidade e mobile:** Modo estático obrigatório, teclado completo, redução de movimento e sem dependência de reflexos para aprovação.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** IDs tocados, acertos, erros, ordem de apresentação e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Alvo duplicado, todos inválidos, animação pausada, alvo sobreposto, toque duplo e tecla Enter.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade acerte o alvo para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/30-acerte-o-alvo.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

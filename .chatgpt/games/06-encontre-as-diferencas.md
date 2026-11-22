# 06. Encontre as diferenças

**Tipo proposto:** `spot_difference` | **Dificuldade de implementação:** Fácil

## Finalidade e experiência do aluno

Observação detalhada e comparação visual.

Duas imagens semelhantes aparecem lado a lado ou em modo alternado. O aluno toca nas regiões onde há diferenças e identifica todas as zonas previamente definidas.

## Estrutura de dados

O ChatGPT ou Claude deve produzir um JSON semanticamente válido e consistente com esta especificação. O exemplo abaixo demonstra a forma dos dados; quantidades podem ser ampliadas conforme o objetivo do professor.

```json
{
  "type": "spot_difference",
  "title": "Segurança no laboratório",
  "images": {
    "before": "assets/cena-a.png",
    "after": "assets/cena-b.png",
    "alt": "Duas cenas de um laboratório"
  },
  "differences": [
    {
      "id": "gloves",
      "x": 0.35,
      "y": 0.42,
      "radius": 0.06,
      "explanation": "Luvas ausentes"
    },
    {
      "id": "goggles",
      "x": 0.68,
      "y": 0.3,
      "radius": 0.07,
      "explanation": "Óculos de proteção"
    }
  ]
}
```

Usar `activitytype="spot_difference"` e adaptar o objeto de `informationjson` quando o fluxo de metadados estiver integrado. No fluxo atual, a IA também precisa entregar o HTML completo com os dados necessários. Os campos acima são uma **proposta de contrato de jogo**, não um formato que o servidor já interpreta automaticamente.

## Construção em HTML, CSS e JavaScript

1. Exigir imagens do mesmo enquadramento e coordenadas normalizadas de 0 a 1, independentes da resolução de exibição.
2. Converter o ponto tocado no elemento para posição relativa, corrigindo letterboxing e object-fit; comparar distância a cada centro ou usar polígonos quando necessário.
3. Contar uma diferença apenas uma vez por ID, em qualquer uma das imagens, e desenhar marcações sincronizadas.
4. Criar modo alternado para telas pequenas e tolerância mínima de toque sem aceitar cliques muito distantes.

## Regras obrigatórias de interação

Estas regras complementam as regras globais de [RULES.md](RULES.md) e fazem parte dos critérios de aceitação. O gerador deve implementá-las e testá-las, não apenas mencioná-las na descrição do jogo.

1. **Coordenadas responsivas:** detectar toques em coordenadas normalizadas dentro da imagem real, descontando margem de `object-fit: contain`; não contar um clique no espaço vazio como diferença.
2. **Toque nas duas imagens:** aceitar a região correspondente em qualquer imagem, marcando o mesmo ID nas duas; cada diferença só pode ser encontrada uma vez.
3. **Zonas sobrepostas:** se duas áreas aceitarem o mesmo ponto, escolher a região mais próxima ainda não encontrada ou pedir desambiguação; não marcar duas diferenças no mesmo toque.
4. **Toque impreciso:** aplicar raio de tolerância suficiente para touch, sem expandir zonas a ponto de aceitar qualquer toque; usar ampliação quando necessário.
5. **Zoom/alternância:** preservar pontos e marcações após zoom, rolagem, redimensionamento e alternância para layout de uma imagem por vez.
6. **Erros e dicas:** toque fora de zona não altera achados; só contar tentativa errada quando essa regra estiver visível. Dica revela uma região não encontrada apenas sob comando explícito.
7. **Acessibilidade:** alternativa por setores numerados com descrições acessíveis e escolha por teclado, sem exigir inspeção puramente visual.
8. **Testes:** duplo toque, clique em margem, hotspots sobrepostos, orientação mobile, imagem cortada e descoberta da última região.

Estruturar o código em `validate(data)`, `init(root, data)`, `render(state)`, `handleAction(action)`, `getState()`, `restoreState(state)` e `destroy()`. Validar o JSON antes de montar a tela, gerar todos os nós com APIs DOM seguras e separar as regras do jogo da animação. `destroy()` deve cancelar listeners, animações e timers remanescentes. O jogo não pode acessar o DOM do Moodle por fora do iframe.

## Feedback e interface

- **Feedback:** Marcar regiões encontradas, exibir explicação pedagógica e número de diferenças faltantes.
- **Acessibilidade e mobile:** Fornecer alternativa textual com regiões nomeadas/numéricas para alunos que não conseguem interagir com imagens.
- **Apresentação:** incluir instruções claras, botão Reiniciar, indicador de progresso quando aplicável, foco visível, contraste suficiente e modo com movimento reduzido.
- **Recursos:** fornecer texto alternativo e tratamento de carregamento para imagens/sons, sem tornar áudio requisito da tarefa.

## Persistência, avaliação e testes

- **Estado mínimo:** IDs localizados, cliques incorretos, dicas utilizadas e conclusão.
- **Salvamento:** carregar `MoodleMCP.progress.load()`, salvar apenas transições significativas com `progress.save({version, location, data})` e usar a versão retornada na escrita seguinte; em conflito, recuperar o estado atual e orientar reconciliação. Respeitar limite documentado de 12 KiB.
- **Conclusão:** mostrar sucesso lúdico separadamente de nota e conclusão acadêmica; `MoodleMCP.completion.complete()` somente se a regra nativa permitir. Gabaritos no cliente são inseguros para nota oficial: quando a atividade exigir avaliação válida, especificar uma verificação no servidor ainda a implementar para este tipo.
- **Testes obrigatórios:** Escala/zoom, object-fit, cliques na margem, toque repetido, imagens desalinhadas e zonas sobrepostas.
- **Fluxo editorial:** testar em celular, teclado e leitor de tela, validar os dados, gerar rascunho, abrir prévia, obter aprovação e só então publicar.

## Prompt de autoria sugerido

> Crie uma atividade encontre as diferenças para mod_mcp com tema, nível e objetivo informados pelo professor. Siga `.chatgpt/games/06-encontre-as-diferencas.md`, gere dados JSON coerentes, implemente toda a interação em HTML/CSS/JavaScript para iframe isolado, inclua feedback formativo, modo touch/teclado, estado retomável e testes manuais. Não confunda pontuação visual com nota Moodle, não dependa de CDN e não publique sem confirmação.

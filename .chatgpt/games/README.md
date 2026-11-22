# Catálogo de jogos educacionais - mod_mcp

Esta pasta contém **30 especificações de jogos** que o ChatGPT ou Claude pode gerar para uma atividade `mod_mcp`. Cada arquivo descreve a dinâmica e a construção de um jogo, sem afirmar que seu motor JavaScript já está instalado no plugin.

## Uso no fluxo atual

1. O professor solicita um jogo e informa conteúdo, público, objetivos, critérios de sucesso e recursos de imagem.
2. A IA seleciona a especificação, adapta exemplos e valida consistência, limites, acentos, gabaritos e alternativas.
3. A IA gera um documento HTML completo com CSS e JavaScript autossuficientes, adequado ao iframe sandboxed `allow-scripts`. Pode entregar o HTML para copiar/colar no gerenciador sem conexão MCP ou usar `mcp_save_content` quando disponível.
4. Se houver conexão com o Moodle, ler `mcp_get_activity`, salvar os metadados com `mcp_save_information` usando o `expectedhash` vigente e seguir o fluxo de prévia/confirmação. Não inventar argumentos de ferramentas, ignorar conflitos nem publicar sem aprovação.
5. O campo `activitytype` e o JSON `informationjson` já existem, mas as estruturas propostas aqui **não são schemas de jogos validados pelo servidor**. Atualmente o validador aplica regras detalhadas apenas quando o tipo é `quiz`. Até existir integração, o HTML deve conter/consumir o JSON gerado e renderizar o jogo por conta própria.

## Contrato proposto para os jogos

- `activitytype`: identificador snake_case indicado no documento (máximo 40 caracteres).
- `informationjson`: objeto com `type`, `title`, `settings` opcionais e os campos específicos do jogo. Campos e exemplos deste catálogo são **contratos propostos**, não APIs já implementadas.
- Funções de cada motor: `init(root, information)`, `validate(information)`, `getState()`, `restoreState(state)`, `destroy()`. Esse é o contrato desejado para futura biblioteca; não confundir com métodos existentes do SDK.
- Renderizar conteúdo externo como texto (`textContent`) e atributos URL seguros; nunca executar HTML/JS recebido por JSON, `eval`, `new Function` ou handlers inline criados de strings.
- Todo jogo deve funcionar em telas pequenas, permitir controle por teclado e toque, informar erros por texto além de cor e respeitar `prefers-reduced-motion`.
- Evitar bibliotecas remotas, cookies, acesso ao DOM Moodle ou chamadas diretas a APIs autenticadas do Moodle a partir do iframe.
- Assets devem ser publicados pelos mecanismos autorizados do Moodle, não por caminhos de máquina ou URLs temporárias.

## Progresso e avaliação

A implementação atual documenta `MoodleMCP.progress.load()` e `progress.save({version, location, data})` para o estado individual, com controle otimista de versão e limite de 12 KiB de JSON. Salvar em marcos importantes (acerto, troca de etapa ou saída), atualizar `version` após cada sucesso e tratar conflitos por releitura/reconciliação. `progress.saveStatus` permite registrar posição/percentual sem equivaler à conclusão acadêmica. `completion.complete()` deve obedecer às regras configuradas pelo professor. Telemetria `tracking.record()` não é prova de nota.

**Não usar um placar do JavaScript como nota oficial.** Os campos de resposta e solução dos jogos podem chegar ao cliente no HTML ou no JSON, e portanto podem ser inspecionados e alterados. O tipo `quiz` do repositório já possui um caminho de correção JSON no servidor; os tipos de jogos desta pasta **não possuem automaticamente** essa correção. Uma avaliação confiável para novos tipos precisa de API específica que mantenha soluções e critérios fora do iframe e revalide tentativas no servidor.

O SDK depende da ponte autenticada do host; sem conexão/offline, o jogo ainda pode rodar com estado transitório no navegador, mas não deve prometer sincronização Moodle automática. Persistência local opcional não substitui a API nem deve conter dados sensíveis.

## Diretrizes pedagógicas

Definir objetivo, pré-requisitos, feedback com explicação, possibilidade de repetição e critérios de sucesso antes de construir a interface. Preferir desafios curtos e progressivos; não exigir reflexos, áudio ou precisão de ponteiro como única forma de participar. Quando o exercício tem múltiplas respostas legítimas, modelar explicitamente os conjuntos de soluções.

## Índice

01. [Jogo da memória](01-jogo-da-memoria.md) - `memory`, Muito fácil
02. [Caça-palavras](02-caca-palavras.md) - `word_search`, Fácil
03. [Arrastar e associar](03-arrastar-e-associar.md) - `drag_match`, Fácil
04. [Quebra-cabeça de imagens](04-quebra-cabeca.md) - `jigsaw`, Fácil em grade; médio com formas irregulares
05. [Coloque na ordem correta](05-coloque-na-ordem.md) - `sequence`, Muito fácil
06. [Encontre as diferenças](06-encontre-as-diferencas.md) - `spot_difference`, Fácil
07. [Descubra a palavra](07-descubra-a-palavra.md) - `word_guess`, Muito fácil
08. [Palavras cruzadas](08-palavras-cruzadas.md) - `crossword`, Médio
09. [Classifique os itens](09-classifique-os-itens.md) - `categorize`, Muito fácil
10. [Identifique na imagem](10-identifique-na-imagem.md) - `image_hotspots`, Fácil
11. [Bingo educativo](11-bingo-educativo.md) - `bingo`, Muito fácil
12. [Verdadeiro ou falso rápido](12-verdadeiro-ou-falso.md) - `true_false`, Muito fácil
13. [Complete as lacunas](13-complete-as-lacunas.md) - `fill_blanks`, Fácil
14. [Quem sou eu?](14-quem-sou-eu.md) - `who_am_i`, Muito fácil
15. [Qual não pertence?](15-qual-nao-pertence.md) - `odd_one_out`, Muito fácil
16. [Conecte os pontos](16-conecte-os-pontos.md) - `connect_dots`, Fácil
17. [Quiz de imagens](17-quiz-de-imagens.md) - `image_quiz`, Muito fácil
18. [Descubra o erro](18-descubra-o-erro.md) - `find_error`, Fácil
19. [Imagem misteriosa](19-imagem-misteriosa.md) - `reveal_image`, Fácil
20. [Labirinto educativo](20-labirinto-educativo.md) - `maze`, Médio
21. [Monte a frase](21-monte-a-frase.md) - `sentence_order`, Muito fácil
22. [Batalha de perguntas](22-batalha-de-perguntas.md) - `quiz_battle`, Fácil
23. [Sudoku educativo](23-sudoku-educativo.md) - `sudoku`, Médio
24. [Dominó educativo](24-domino-educativo.md) - `domino`, Fácil
25. [Jogo dos sete erros](25-jogo-dos-sete-erros.md) - `seven_errors`, Fácil
26. [Trilha do conhecimento](26-trilha-do-conhecimento.md) - `knowledge_path`, Fácil
27. [Escape room educativo](27-escape-room-educativo.md) - `escape_room`, Médio
28. [Monte o processo](28-monte-o-processo.md) - `process_builder`, Fácil
29. [Escolha o caminho](29-escolha-o-caminho.md) - `branching_story`, Médio
30. [Acerte o alvo](30-acerte-o-alvo.md) - `target_click`, Fácil

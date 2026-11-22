# Regras obrigatórias de interação para os jogos do mod_mcp

Este documento estabelece o comportamento padrão que o ChatGPT ou Claude **deve implementar no HTML/CSS/JavaScript gerado**, além das regras de cada arquivo numerado. São especificações de autoria e critérios de teste: não indicam que o mod_mcp já possua 30 motores prontos. O HTML pode ser salvo diretamente pelo fluxo de copiar/colar ou pelo conector de autoria, sem modificar a arquitetura do plugin.

**Precedência:** a regra específica do jogo prevalece quando definir intencionalmente uma exceção (por exemplo, carta da memória não pode ser alterada depois de pareada). Uma opção configurável só pode alterar comportamento quando for declarada no JSON, aplicada coerentemente na interface e explicada ao aluno. Não criar opções implícitas, valores mágicos ou regras contraditórias entre mouse, toque e teclado.

## 1. Estado, entradas e correções

1. Manter **uma fonte de verdade em JavaScript** para o estado lógico, com IDs estáveis para itens, cartas, posições, respostas, cenas e ligações. O DOM é representação do estado, não o banco de dados do jogo; não inferir resposta por transformações CSS, posição em pixels ou texto visual.
2. Antes de confirmação definitiva, permitir **alterar, substituir, remover ou reposicionar** escolhas sempre que a mecânica comportar correção. Nunca exigir reiniciar tudo para corrigir uma única ação. Definir explicitamente as exceções de irreversibilidade (por exemplo, carta já pareada ou escolha final confirmada).
3. Distinguir estados **não selecionado**, **selecionado provisoriamente**, **confirmado**, **correto**, **incorreto**, **bloqueado** e **concluído** conforme a mecânica. Não usar um único booleano `done` para todas as etapas.
4. Ações inválidas ou canceladas não podem apagar acertos, mover outra peça, alterar pontuação nem deixar elementos presos à interação. Explicar o problema por texto, sem depender exclusivamente de animação ou cor.
5. **Um gesto lógico = no máximo uma alteração:** impedir duplicidade por `click` + `pointerup`, duplo toque, Enter repetido, promessa resolvida duas vezes ou animação atrasada. Usar transições idempotentes e não apenas esconder botões.
6. Ao terminar a atividade, congelar somente as ações que mudariam o resultado. Permitir consultar respostas, feedback e instruções, além de reiniciar sob comando explícito.
7. Não contabilizar erro por tocar fora de destino, pressionar botão desabilitado, rolar a tela, cancelar gesto, digitar caractere inválido ou selecionar repetidamente o mesmo item, salvo regra pedagógica excepcional claramente anunciada.

## 2. Arraste, toque, teclado e foco

1. Quando houver arraste, oferecer caminho alternativo **selecionar origem -> selecionar destino**, com a mesma validação lógica. Para reordenação, disponibilizar controles de mover acima/abaixo, esquerda/direita ou para posição especificada.
2. Implementar pointerdown/move/up/cancel com **Pointer Events**, aplicando captura quando necessária. Diferenciar toque de gesto de rolagem e evitar impedir o scroll da página inteira; em `pointercancel`, restaurar estado anterior.
3. **Destino ocupado:** nunca sobrescrever elemento silenciosamente. A regra do jogo deve especificar se troca, inserção/deslocamento, devolução ao início ou rejeição. Essa regra deve valer também no teclado.
4. **Foco estável:** depois de mover/validar/re-renderizar, restaurar foco no controle correspondente quando houver continuidade, sem deslocá-lo inesperadamente para o início do documento. Não reconstruir toda a árvore de inputs a cada tecla quando isso quebra foco ou composição.
5. Tab e Shift+Tab permitem chegar aos controles e sair da atividade; não criar armadilhas. Enter e Espaço ativam ações; setas são usadas quando fizerem sentido para a grade/lista, sem impedir escrita normal em campos.
6. Em campo de texto preenchido, permitir edição e substituição; onde a mecânica define entrada de **um único caractere**, sobrescrever o existente ao digitar outro, respeitar composição de caracteres (`compositionstart`/`compositionend`) e evitar avanço duplo.
7. O próximo foco deve obedecer à **estrutura lógica da atividade**, não à ordem incidental dos nós DOM. Nunca pular campo preenchido apenas porque ele já contém resposta, salvo configuração expressa para avançar a lacunas vazias.
8. Para interfaces que dependem de toque em imagens, usar coordenadas normalizadas na área útil do conteúdo visual e corrigir transformações de zoom, `object-fit`, margens e redimensionamentos.

## 3. Respostas, feedback e navegação

1. Diferenciar **resposta provisória** de **resposta enviada**. Se houver botão Confirmar, permitir revisar escolhas antes dele; depois, não aceitar segunda submissão por acidente.
2. Mostrar feedback claro de acerto e erro com explicação pedagógica e indicação do que fazer a seguir. Não depender apenas de som, cor, vibração, confete ou ícone.
3. **Não ocultar feedback instantaneamente.** Usar botão Próximo ou intervalo configurável com tempo suficiente; caso haja transição automática, assegurar opção acessível de pausar e ler o retorno.
4. Uma dica não é automaticamente um acerto. Registrar dicas separadamente e declarar o efeito opcional sobre a pontuação. Não revelar a resposta certa antes da tentativa quando isso inviabiliza o objetivo.
5. Ao navegar para trás, não duplicar eventos, pontos ou recompensas. Deixar claro quando é **revisão somente leitura** e quando uma decisão anterior pode ser alterada.
6. Permitir respostas equivalentes quando o conteúdo assim exigir, usando lista explícita de respostas/ordens/grafos aceitos. Não fazer comparação frágil por texto literal, concatenação de palavras ou posição visual.
7. Temporizador só quando indicado pelo professor. Sempre oferecer alternativa que não dependa exclusivamente de velocidade ou reflexo; pausar, expirar e retomar precisam ter regra explícita.

## 4. Progresso, reinício, offline e conclusão

1. `getState()` deve retornar objeto JSON pequeno e determinístico (IDs, escolhas, ordenação, posição, tentativas, dicas e status). `restoreState(state)` deve validar formato e IDs atuais; estado inválido não pode quebrar a atividade.
2. Persistir **transições significativas**, nunca frame de animação ou todo movimento do ponteiro. Carregar estado com `MoodleMCP.progress.load()` e salvar com `progress.save({version,location,data})`, atualizando a versão após sucesso e tratando conflitos por releitura/reconciliação; seguir o limite documentado de 12 KiB.
3. Progresso de rodada e foco são distintos: ao retomar, reconstruir tabuleiro e estado, mas **não roubar o foco** de menus, controles ou de outro elemento da página. Cancelar gestos e timers temporários na reidratação.
4. O jogo deve funcionar como HTML independente mesmo sem a ponte do Moodle; a ausência de `window.MoodleMCP` não pode gerar exceção fatal. Nesse caso, sinalizar honestamente que não há sincronização confirmada de progresso. Não prometer escrita offline que a API não suporta.
5. Reiniciar jogo com progresso salvo exige ação consciente e confirmação de perda de estado quando apropriado. O botão Reiniciar deve cancelar timers/gestos, repor ordem e dados conforme política de embaralhamento e limpar a tentativa atual sem duplicar eventos de conclusão.
6. Distinguir pontuação lúdica e status de interação de **nota oficial** e **conclusão acadêmica**. Nunca escrever gradebook diretamente pelo HTML nem aceitar score enviado pelo cliente como correção confiável. Respeitar regras nativas de conclusão configuradas.
7. Se armazenar o gabarito no JSON/HTML cliente, tratá-lo como **não secreto**. Para avaliação oficial de novos tipos, projetar validação/autorização no servidor antes de prometer uma nota confiável.

## 5. Acessibilidade, responsividade e segurança

1. Usar controles HTML semânticos (`button`, `input`, `label`, listas) e foco visível; fornecer mensagens de estado para tecnologia assistiva sem criar anúncios excessivos ou mudar foco arbitrariamente.
2. Todas as mecânicas precisam de alternativa para teclado e toque. Imagens que contêm a resposta visual exigem descrição equivalente ou rota pedagógica acessível; não usar apenas cores, coordenadas minúsculas ou movimento rápido.
3. Respeitar `prefers-reduced-motion`, alto contraste quando aplicável, áreas de toque adequadas e layouts responsivos sem scroll horizontal desnecessário. Animação nunca deve impedir conclusão.
4. Renderizar dados do JSON como texto seguro (`textContent`) e validar URLs de mídia. Não executar scripts/HTML do conteúdo, `eval`, `new Function`, handlers dinâmicos inseguros, CDN obrigatório ou acesso ao DOM/cookies do Moodle fora do sandbox.
5. Validar estrutura, IDs únicos, referências, limites, respostas, recursos disponíveis e resolubilidade do jogo **antes de publicar**. Imagem ausente, JSON inválido ou condição impossível devem exibir erro compreensível, não uma tela branca.
6. Não enviar informações sensíveis do aluno para serviços externos; o progresso pertence ao usuário autenticado e o cliente não determina identidade, capacidades ou privilégios.

## 6. Matriz mínima de testes obrigatórios

Para cada arquivo numerado, além dos testes específicos descritos nele, validar:

- **Mouse:** primeira ação, ação válida, ação errada, duplo clique, clique fora, correção, desfazer quando aplicável e conclusão.
- **Celular:** toque simples e repetido, rolagem durante arraste, `pointercancel`, mudança de orientação, teclado virtual e imagem redimensionada.
- **Teclado:** Tab/Shift+Tab, Enter/Espaço, setas quando aplicáveis, entrada/Backspace, foco após re-render e alternativa completa ao arraste.
- **Estado:** recarregar após acerto, erro, correção, último passo e conclusão; validar preservação de IDs e impedir que timers antigos alterem estado restaurado.
- **Dados:** JSON vazio/inválido, IDs duplicados, referências inexistentes, gabarito ambíguo, configuração impossível, ausência de imagem e conteúdo com caracteres especiais.
- **Acessibilidade:** leitor de tela, feedback textual, foco visível, redução de movimento, modo sem limite de tempo quando relevante e controle sem precisão de ponteiro.

**Critério de aceite:** o HTML gerado deve operar com essas regras, demonstrar os fluxos nos testes manuais e não apenas citá-las num texto ou comentário. A implementação precisa obedecer ao arquivo específico e a este documento ao mesmo tempo.

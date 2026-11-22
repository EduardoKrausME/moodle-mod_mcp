# Testes do catálogo

Execute a partir da raiz do repositório:

```bash
node --test docs/pt_br/tests/catalog.test.mjs
```

O teste percorre as oito categorias e todos os exemplos registrados, verifica os arquivos HTML, sintaxe do JavaScript embutido, referências dos manifestos e isolamento das prévias. Não exige instalação de dependências, Moodle nem servidor HTTP.

Para testar o comportamento visual e as interações manualmente:

```bash
python3 -m http.server 8000 -d docs
```

Abra http://localhost:8000/pt_br/, selecione os oito menus, faça buscas, abra os exemplos e experimente os controles. O teste estático não substitui a inspeção em navegador.

Testes adicionais para a nova organização de jogos:

```bash
node --test docs/pt_br/tests/catalog.test.mjs docs/pt_br/tests/jigsaw.test.mjs docs/pt_br/tests/sequence.test.mjs
```

A galeria utiliza `games/examples.json` como índice e exige um `examples.json` dentro de cada pasta `game-*`; os testes verificam os caminhos e a consistência entre os manifestos.

O jogo 07 (Descubra a palavra) possui testes próprios para letras repetidas, acentuação, espaços, hífen, limite de erros e retomada de progresso:

```bash
node --test docs/pt_br/tests/word-guess.test.mjs
```

Para a cruzadinha de Ciências Naturais (jogo 08), execute:

```bash
node --test docs/pt_br/tests/crossword.test.mjs
```

O teste verifica a consistência dos cruzamentos, numeração, acentuação, limites da grade, integridade de estados e sincronização dos catálogos.

Para o jogo 09 (Classifique os itens), execute:

```bash
node --test docs/pt_br/tests/categorize.test.mjs
```

Esse teste cobre categorias inválidas ou vazias, itens multicategoria, IDs desconhecidos, desfazer, conferência por conjunto, estado de progresso, acessibilidade e sincronização dos dois manifestos.

Para o jogo 10 (Identifique na imagem), execute:

```bash
node --test docs/pt_br/tests/image-hotspots.test.mjs
```

O teste cobre validação de coordenadas, identificação por IDs, seleção errada e repetida, duas formas de interação, zoom proporcional, descrição textual acessível e sincronização entre os dois JSONs.

Para o jogo 11 (Bingo educativo de Cidadania digital), execute:

```bash
node --test docs/pt_br/tests/bingo.test.mjs
```

O teste cobre pool insuficiente, cartelas com seed estável, sorteio sem repetição, resposta por ID, ausência de item na cartela, vitória por linha/coluna/diagonal, restauração de estado, novas cartelas, acessibilidade e sincronização dos JSONs.

Para o jogo 12 (Verdadeiro ou falso rápido - Mitos e fatos da Astronomia), execute:

```bash
node --test docs/pt_br/tests/true-false.test.mjs
```

O teste cobre booleano recebido como string, clique duplo, explicações, textos longos, retorno à afirmação anterior, retomada de progresso, funcionamento offline e sincronização dos catálogos.

Para o jogo 13 (Complete as lacunas - O ciclo da água), execute:

```bash
node --test docs/pt_br/tests/fill-blanks.test.mjs
```

O teste abrange estrutura dos segmentos, lacunas com respostas repetidas e IDs exclusivos, sinônimos, textos com pontuação, termos compostos, opções ausentes do banco, proteção contra HTML inseguro, seleção e digitação, conferência, dicas, restauração de estado e sincronização dos catálogos.

Para o jogo 14 (Quem sou eu? - Fundamentos da Computação), execute:

```bash
node --test docs/pt_br/tests/who-am-i.test.mjs
```

Este teste cobre aliases, acentuação, pontuação, repetição de palpites, pontuação lúdica, dicas vazias, tentativas esgotadas, encerramento após a última dica, desistência, retorno de sessão, funcionamento sem SDK, acessibilidade e sincronização dos manifestos.

Para o jogo 15 (Qual não pertence? - Ciências e Tecnologia), execute:

```bash
node --test docs/pt_br/tests/odd-one-out.test.mjs
```

Esse teste cobre critérios e explicações ausentes, respostas inválidas, itens iguais, ID da exceção desconhecido, zero ou múltiplas exceções, embaralhamento reproduzível, respostas repetidas, restauração de progresso, acessibilidade e sincronização dos dois catálogos.

Para o jogo 16 (Conecte os pontos - A viagem da água), execute:

```bash
node --test docs/pt_br/tests/connect-dots.test.mjs
```

O teste cobre coordenadas inválidas, IDs e pontos repetidos, seleção fora da ordem, clique fora de ponto, zoom, backtrack, teclado, dicas, conclusão, persistência opcional e igualdade dos manifestos.

Para o jogo 17 (Quiz de imagens - Instrumentos científicos), execute:

```bash
node --test docs/pt_br/tests/image-quiz.test.mjs
```

Os testes verificam as seis ilustrações SVG locais, textos alternativos, imagens ausentes ou duplicadas, instrumentos visualmente semelhantes, gabaritos e IDs, embaralhamento determinístico, modo inverso, falha de carregamento, responsividade, respostas repetidas, estado restaurado, sincronização dos manifestos e salvamento opcional via SDK.

Para o jogo 18 (Descubra o erro - Revisão de código web), execute:

```bash
node --test docs/pt_br/tests/find-error.test.mjs
```

Os testes verificam trechos HTML perigosos renderizados como texto, erros em linhas inexistentes, múltiplos erros, correção antes/depois, seleções duplicadas, revelação parcial da solução, estados restaurados inconsistentes, linhas com indentação, interação por teclado, acessibilidade e sincronização dos manifestos.

Para o jogo 19 (Imagem misteriosa - Biodiversidade), execute:

```bash
node --test docs/pt_br/tests/mystery-image.test.mjs
```

Esse teste cobre três SVGs autorais incorporados, grade 4×4, proporção estável, revelação determinística, 0 peças, pistas textuais, aliases, limite de tentativas, repetição de palpite, revelação integral sem acerto, desistência, imagem indisponível ou lenta, restauração de estado, SDK opcional e igualdade dos dois catálogos.

Para o jogo 20 (Labirinto educativo - Missão Energia Limpa), execute:

```bash
node --test docs/pt_br/tests/maze.test.mjs
```

A suíte cobre BFS e solvabilidade, duas saídas, grades irregulares, portões obrigatórios, perguntas bloqueadas, erros e acertos, passos diagonais proibidos, paredes, reinício, progresso inválido, navegação por teclado, toque, diálogo nativo, SDK opcional e sincronização do catálogo.

Para o jogo 21 (Monte a frase - Ciência em palavras), execute:

```bash
node --test docs/pt_br/tests/sentence-order.test.mjs
```

O teste cobre palavras repetidas com IDs próprios, equivalência entre ocorrências iguais, pontuação, duas ordens gramaticais válidas, gabaritos incompletos ou desconhecidos, embaralhamento determinístico, seleção e movimento dos tokens, remoção, desfazer, limpar, correção, tentativa parcial, estado restaurado, acessibilidade e igualdade dos manifestos.

Para o jogo 22 (Batalha de perguntas - Escudo Digital), execute:

```bash
node --test docs/pt_br/tests/quiz-battle.test.mjs
```

A suíte verifica configuração solo/robô e relógio opcional, seleção única, clique duplo, embaralhamento, pontuação por combo, tempo expirado, teto de bônus, recomeço com confirmação, corrida entre clique e expiração, aba oculta, empate, placar após retomada, sessão sem áudio, acessibilidade e sincronização dos manifestos.

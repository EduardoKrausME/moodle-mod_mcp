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

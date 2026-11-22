# Galeria de exemplos do Mod MCP (pt-BR)

Esta pasta é um site estático. Em GitHub Pages, configure a origem para a branch `master`, diretório `/docs`. O ponto de entrada é `docs/index.html`, que abre `docs/pt_br/index.html`.

## Organização

- `catalog.json`: lista das oito categorias, com o manifesto de cada uma.
- `<categoria>/examples.json`: catálogo dos exemplos, seus textos e caminhos.
- `<categoria>/<slug>.html`: exemplos das sete categorias gerais, em HTML autossuficiente.
- `games/examples.json`: índice consolidado dos jogos, utilizado pela aba Games da galeria.
- `game-<modalidade>/examples.json`: lista dos exemplos daquela modalidade.
- `game-<modalidade>/<nome>.html`: um arquivo HTML completo para cada exemplo da modalidade, com CSS e JavaScript próprios.
- `app.js` e `styles.css`: somente a interface da galeria.

Para cadastrar um jogo, crie `game-<modalidade>/<nome>.html`, registre-o no `game-<modalidade>/examples.json` e no índice `games/examples.json`. Os testes verificam se as duas listas concordam. Os outros tipos continuam em `<categoria>/<nome>.html` e usam o `examples.json` de sua categoria. O site carrega apenas o catálogo da categoria selecionada; não é necessário editar `app.js` para criar um exemplo.

## Campos do manifesto

`version` (1), `category`, `title`, `description`, `examples` (array). Cada exemplo tem `id`, `title`, `description`, `howItWorks`, `path`, `minutes` e `features`; em jogos, pode ter `subtype`. O campo `path` deve seguir `categoria/nome.html` nos sete modos gerais, ou `game-<modalidade>/nome.html` nos jogos. Ele é relativo a `docs/pt_br/`, sem `../`, URLs externas ou caminho absoluto. Ver também `manifest.schema.json`.

## Previews e segurança

Os cards exibem um iframe de **1024 × 640 pixels** com `transform: scale(...)` baseado na largura do card; isso mantém o layout real do exemplo sem tentar reescrever seu HTML. O iframe é isolado com `sandbox="allow-scripts"`, **sem** `allow-same-origin`, e a prévia ampliada também. Os iframes dos cards são iniciados sob demanda, e a grade mostra no máximo 12 itens por vez. A prévia ampliada permite experimentar os controles, abrir o HTML em outra aba, visualizar e copiar o código.

Os exemplos são demonstrações independentes do Moodle. O resultado de um quiz, pesquisa ou jogo na galeria é **local ao navegador e não é uma nota, resposta registrada ou conclusão no Moodle**. Para persistência, avaliações reais e publicação, integre o HTML ao fluxo oficial e consulte [API.md](../../API.md). Não coloque tokens, segredos ou URLs privados nos exemplos.

Os arquivos `games/*.html` foram migrados para suas pastas específicas. A pasta `games/` permanece apenas como índice da galeria, e cada modalidade pode reunir quantos exemplos forem necessários.

## Teste local

Na raiz do repositório, execute `python3 -m http.server 8000 -d docs` e abra `http://localhost:8000/pt_br/`. Não abra `index.html` usando `file://`, pois os navegadores restringem `fetch()` de JSON local.

Não repita cabeçalho, rodapé institucional, nome do curso ou dados técnicos do Moodle dentro dos exemplos: o plugin já fornece essas informações no contêiner que hospeda a atividade.

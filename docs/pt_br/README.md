# Galeria de exemplos do Mod MCP (pt-BR)

Esta pasta é um site estático. Em GitHub Pages, configure a origem para a branch `master`, diretório `/docs`. O ponto de entrada é `docs/index.html`, que abre `docs/pt_br/index.html`.

## Organização

- `catalog.json`: lista das oito categorias, com o manifesto de cada uma.
- `<categoria>/examples.json`: catálogo dos exemplos, seus textos e caminhos.
- `<categoria>/<slug>.html`: HTML completo, autossuficiente, contendo CSS e JavaScript necessários para executar o exemplo.
- `app.js` e `styles.css`: somente a interface da galeria.

Para cadastrar um exemplo, crie o HTML dentro da pasta de sua categoria e adicione uma entrada ao `examples.json` correspondente. O site carrega o catálogo e, ao selecionar uma categoria no menu superior, busca apenas o JSON dessa categoria. Não é necessário editar `app.js`.

## Campos do manifesto

`version` (1), `category`, `title`, `description`, `examples` (array). Cada exemplo tem `id`, `title`, `description`, `howItWorks`, `path`, `minutes` e `features`; em jogos, pode ter `subtype`. O campo `path` deve seguir o formato `categoria/slug.html`, sem links externos ou caminhos relativos para outras pastas. Ver também `manifest.schema.json`.

## Previews e segurança

Os cards exibem um iframe de **1024 × 640 pixels** com `transform: scale(...)` baseado na largura do card; isso mantém o layout real do exemplo sem tentar reescrever seu HTML. O iframe é isolado com `sandbox="allow-scripts"`, **sem** `allow-same-origin`, e a prévia ampliada também. Os iframes dos cards são iniciados sob demanda, e a grade mostra no máximo 12 itens por vez. A prévia ampliada permite experimentar os controles, abrir o HTML em outra aba, visualizar e copiar o código.

Os exemplos são demonstrações independentes do Moodle. O resultado de um quiz, pesquisa ou jogo na galeria é **local ao navegador e não é uma nota, resposta registrada ou conclusão no Moodle**. Para persistência, avaliações reais e publicação, integre o HTML ao fluxo oficial e consulte [API.md](../../API.md). Não coloque tokens, segredos ou URLs privados nos exemplos.

## Teste local

Na raiz do repositório, execute `python3 -m http.server 8000 -d docs` e abra `http://localhost:8000/pt_br/`. Não abra `index.html` usando `file://`, pois os navegadores restringem `fetch()` de JSON local.

Não repita cabeçalho, rodapé institucional, nome do curso ou dados técnicos do Moodle dentro dos exemplos: o plugin já fornece essas informações no contêiner que hospeda a atividade.

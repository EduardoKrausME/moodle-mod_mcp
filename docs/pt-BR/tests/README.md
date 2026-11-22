# Testes do catálogo

Execute a partir da raiz do repositório:

```bash
node --test docs/pt-BR/tests/catalog.test.mjs
```

O teste percorre as oito categorias e todos os exemplos registrados, verifica os arquivos HTML, sintaxe do JavaScript embutido, referências dos manifestos e isolamento das prévias. Não exige instalação de dependências, Moodle nem servidor HTTP.

Para testar o comportamento visual e as interações manualmente:

```bash
python3 -m http.server 8000 -d docs
```

Abra http://localhost:8000/pt-BR/, selecione os oito menus, faça buscas, abra os exemplos e experimente os controles. O teste estático não substitui a inspeção em navegador.

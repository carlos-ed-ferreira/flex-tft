# FlexTFT

Aplicação Laravel + Vue 3 + Inertia.js para gerenciamento e simulação de composições do TFT (Teamfight Tactics).

**Stack:** Laravel 12 · Vue 3 · Inertia.js · Vite · Tailwind CSS · MySQL · Docker (Laradock)

## Requisitos

- Git
- Docker e Docker Compose
- `make`

## Como iniciar

```sh
git clone --recurse-submodules https://github.com/carlos-ed-ferreira/flex-tft.git
cd flex-tft
make setup
make dev
```

Se você já clonou sem os submodules, inicialize o Laradock antes do setup:

```sh
git submodule update --init --recursive
make setup
make dev
```

O `make setup` faz a configuração inicial do projeto, preparando os arquivos `.env`, containers, dependências, chave da aplicação, migrations e build de produção. Em seguida, o `make dev` sobe o ambiente de desenvolvimento com migrations, Vite, queue e pail em foreground.

O Laradock é versionado como Git submodule. Use `--recurse-submodules` ao clonar ou rode `git submodule update --init --recursive` antes do primeiro setup.

Todos os comandos devem ser executados na raiz do projeto, em `flex-tft/`.

## O que o setup prepara

- `laradock/.env`
- `app/.env`
- `app/.env.testing`
- Banco MySQL principal `flex-tft`
- Dependências PHP e JS
- Chave da aplicação
- Migrations
- Build inicial de produção

## Comandos principais

| Comando                | Descrição                                                         |
| ---------------------- | ----------------------------------------------------------------- |
| `make dev`             | Sobe containers, migra e inicia Vite + queue + pail em foreground |
| `make up`              | Sobe containers em background                                     |
| `make stop`            | Para os containers sem removê-los                                 |
| `make down`            | Para e remove os containers                                       |
| `make create-database` | Cria o banco principal, se necessário                             |
| `make test`            | Roda a suite de testes PHPUnit                                    |
| `make sync`            | Sincroniza os dados de campeões, itens e traits do TFT            |
| `make strip-comments`  | Remove comentários do código em `app/` e roda format              |
| `make format`          | Formata PHP (Pint) e JS/Vue (Prettier)                            |

## Padrão do projeto

O projeto mantém o código-fonte sem comentários. A preferência é por nomes claros, funções pequenas e estrutura direta. Se houver comentários temporários durante o desenvolvimento, use `make strip-comments` antes de formatar ou finalizar as alterações.

## Mais detalhes

Para o fluxo do dia a dia depois de clonar e abrir o projeto, consulte [app/README.md](app/README.md).

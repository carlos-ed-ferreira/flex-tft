# FlexTFT

Aplicação Laravel + Vue 3 + Inertia.js para gerenciamento e simulação de composições do TFT (Teamfight Tactics).

**Stack:** Laravel 12 · Vue 3 · Inertia.js · Vite · Tailwind CSS · MySQL · Docker (Laradock)

O padrão do projeto é manter o código-fonte sem comentários, priorizando nomes claros, funções pequenas e estrutura direta; quando necessário, use `make strip-comments` para remover comentários antes da formatação.

---

## Requisitos

- Docker e Docker Compose
- `make`

---

## Setup do ambiente

```sh
make setup
make dev
```

O `make setup` faz a configuração inicial do projeto, preparando os arquivos `.env`, containers, dependências, chave da aplicação, migrations e build de produção, enquanto o `make dev` sobe o ambiente de desenvolvimento com migrations, Vite, queue e pail em foreground.

O Laradock fica na raiz do projeto como Git submodule. Ao clonar o repositório pela primeira vez, use `git clone --recurse-submodules` ou rode `git submodule update --init --recursive` antes do `make setup`.

---

## Comandos do dia a dia

Todos os comandos são executados na **raiz do projeto** (pasta `flex-tft/`), não dentro de `app/`.

### Ambiente

| Comando      | Descrição                                                         |
| ------------ | ----------------------------------------------------------------- |
| `make dev`   | Sobe containers, migra e inicia Vite + queue + pail em foreground |
| `make up`    | Sobe containers em background (com health check do MySQL)         |
| `make stop`  | Para os containers sem removê-los                                 |
| `make down`  | Para e remove os containers                                       |
| `make shell` | Abre bash interativo no container workspace                       |
| `make logs`  | Exibe logs em tempo real (mysql, nginx, workspace)                |

### Banco de dados

| Comando                | Descrição                                           |
| ---------------------- | --------------------------------------------------- |
| `make create-database` | Cria o banco principal `flex-tft`, se necessário    |
| `make migrate`         | Cria o banco principal e roda `php artisan migrate` |

### Testes

| Comando     | Descrição                                                        |
| ----------- | ---------------------------------------------------------------- |
| `make test` | Roda a suite de testes PHPUnit contra o banco `flex-tft-testing` |

Os testes usam MySQL (banco `flex-tft-testing`) para fidelidade com o ambiente de produção. O banco é criado automaticamente pelo `make test` se não existir.

### Dados TFT

| Comando            | Descrição                                                                                          |
| ------------------ | -------------------------------------------------------------------------------------------------- |
| `make sync`        | Sincroniza campeões, itens e traits da Community Dragon (usa o set definido em TftSyncCommand.php) |
| `make sync set=XX` | Sincroniza os dados do set XX especificado                                                         |

Execute `make sync` sempre que:

- Atualizar para um novo set do TFT
- Os dados de campeões/itens/traits estiverem desatualizados

### Formatação de código

| Comando               | Descrição                                            |
| --------------------- | ---------------------------------------------------- |
| `make strip-comments` | Remove comentários do código em `app/` e roda format |
| `make format`         | Formata PHP (Pint) e JS/Vue (Prettier)               |
| `make format-check`   | Verifica formatação sem aplicar                      |

---

## Variáveis de ambiente

| Arquivo            | Descrição                                  |
| ------------------ | ------------------------------------------ |
| `app/.env`         | Configuração principal da aplicação        |
| `app/.env.testing` | Configuração de banco de dados para testes |
| `laradock/.env`    | Configuração dos containers Docker         |

O `make setup` cria esses arquivos automaticamente a partir dos respectivos `.example`.

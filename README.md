# Renda Viva — backend

Laravel 12 + Sanctum, mesmo padrão do `vitality-Back`: Bearer token puro (sem cookie/CSRF),
`/api/login` e `/api/criar-usuario` públicos em `routes/api.php`, resto protegido por
`auth:sanctum`. Tabelas de domínio em PT-BR, mesmo padrão do vitality-Back (`alimento`, `refeicao`,
`dieta`...).

## Como rodar

```bash
composer install
cp .env.example .env   # já vem com FRONTEND_URL=http://localhost:4200 e DB_* pro Postgres local
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001
```

Banco: **PostgreSQL** (`renda_viva`, mesma instância local do `vitality-Back` — usuário `postgres`).
Se o banco ainda não existir: `psql -h localhost -U postgres -c "CREATE DATABASE renda_viva;"`.
Testes automatizados usam SQLite em memória (`phpunit.xml`), nunca o Postgres de desenvolvimento —
mesma regra do `vitality-Back`.

## Rotas

| Rota                             | Observação                                              |
| --------------------------------- | -------------------------------------------------------- |
| `POST /api/login`                 | `{email, password}` → `{token, user}`. 404/401 por campo. |
| `POST /api/criar-usuario`         | `{name, email, password, password_confirmation}`.        |
| `GET /api/user/get-with-token`    | Protegida — usuário autenticado a partir do token.        |
| `POST /api/logout`                | Protegida — revoga o token atual.                         |

Ainda faltam os endpoints de domínio (renda/gasto/obrigação fixa/colchão) — as tabelas e models já
existem, os controllers/rotas são o próximo passo.

## Tabelas

| Tabela               | Colunas principais                                                                          | Papel                                                       |
| --------------------- | --------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| `categorias_gasto`    | `nome, icone, cor`                                                                             | Catálogo global seedado (10 categorias) — sem `usuario_id`.  |
| `obrigacoes_fixas`    | `usuario_id, descricao, valor, dia_vencimento, ativa`                                          | Aluguel, assinaturas — sempre descontadas primeiro.          |
| `rendas`               | `usuario_id, descricao, fonte, valor, data_recebimento, recorrente`                           | `data_recebimento` é a data real do dinheiro, nunca projetada. |
| `gastos`               | `usuario_id, categoria_gasto_id (nullable), obrigacao_fixa_id (nullable), descricao, valor, data` | Lançamento de gasto.                                          |
| `movimentos_colchao`  | `usuario_id, valor (+/-), tipo (enum), descricao, data`                                        | Livro-razão do colchão — saldo = `SUM(valor)` por usuário.    |

`movimentos_colchao.tipo` é `enum` (`aporte_automatico`, `aporte_manual`, `saque_automatico`,
`saque_manual`) — Laravel emite um `CHECK` constraint no Postgres, então o banco recusa qualquer
valor fora da lista. Todo `valor` monetário é `decimal(12,2)`, nunca float.

Todo model de domínio tem `scopeDoUsuario(int $usuarioId)` — todo endpoint deve filtrar por ele,
nunca listar sem escopo de dono (gap conhecido do vitality-Back que aqui já nasce corrigido).

## Decisões

- **Nunca adicionar `EnsureFrontendRequestsAreStateful`** em `bootstrap/app.php` — essa middleware
  promove qualquer request com header `Origin` pra sessão+CSRF, mesmo em rotas de `routes/api.php`.
  Já foi um bug real no `vitality-Back` (ver o `CLAUDE.md` de lá); aqui nasce sem esse problema
  porque as rotas de auth já entram direto em `routes/api.php`.
- **Colchão alimentado só por fechamento mensal automático** (não a cada lançamento) — um comando
  agendado compara renda real vs. linha de base no fim do mês e lança o movimento (aporte se
  sobrou, saque se faltou). Ainda não implementado — próximo passo depois dos endpoints de CRUD.

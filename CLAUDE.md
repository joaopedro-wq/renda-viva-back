# Renda Viva — Backend

Laravel 12 + Sanctum. Backend do app de gestão financeira pra quem não tem salário fixo — ver o
`CLAUDE.md` do frontend (`../renda-viva-front`) pro contexto de produto completo. Este documento é
a referência viva do backend — sempre que uma decisão de arquitetura mudar ou uma tabela for
alterada, atualize aqui. Roadmap de features em
[`../renda-viva-front/PROXIMAS-FEATURES.md`](../renda-viva-front/PROXIMAS-FEATURES.md) — comece
por lá antes de implementar algo novo (é um arquivo só, compartilhado entre os dois repos).

## Stack

- **Laravel 12**, PHP 8.4. Mesmo padrão do `vitality-Back`: Bearer token puro (Sanctum), sem
  cookie/sessão pra API — `/api/login` e `/api/criar-usuario` já nascem em `routes/api.php`
  (fora do bloco `web`), então **este projeto nunca teve** o bug de CSRF que o vitality-Back
  teve (`EnsureFrontendRequestsAreStateful` promovendo request com `Origin` pra sessão mesmo em
  rota de API) — não adicionar essa middleware em `bootstrap/app.php` por engano.
- **PostgreSQL** em dev (banco `renda_viva`, mesma instância local do `vitality-Back` — usuário
  `postgres`). **Testes automatizados usam SQLite em memória** (`phpunit.xml`), nunca o Postgres
  de dev — mesma regra do `vitality-Back`, existe pra testes não colidirem com dado real.
- **Tabelas de domínio em PT-BR** (`rendas`, `gastos`, `categorias_gasto`, `obrigacoes_fixas`,
  `movimentos_colchao`) — mesmo padrão do `vitality-Back` (`alimento`, `refeicao`, `dieta`,
  `meta`, `recomendacao`). A tabela `users` (Laravel/Sanctum) fica em inglês, é a única exceção —
  é convenção do framework, não do domínio.
- Todo valor monetário é `decimal(12,2)` — nunca `float`/`double`.

## Como rodar

```bash
composer install
cp .env.example .env   # já vem com FRONTEND_URL=http://localhost:4200 e DB_* pro Postgres local
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001
```

Se o banco `renda_viva` ainda não existir:
`psql -h localhost -U postgres -c "CREATE DATABASE renda_viva;"`.

## Tabelas

| Tabela               | Colunas principais                                                                                | Papel                                                                                                                        |
| -------------------- | ------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `categorias_gasto`   | `nome, icone, cor`                                                                                | Catálogo global seedado (10 categorias) — sem `usuario_id`.                                                                  |
| `obrigacoes_fixas`   | `usuario_id, descricao, valor, dia_vencimento, ativa`                                             | Aluguel, assinaturas — sempre descontadas primeiro no cálculo semanal.                                                       |
| `rendas`             | `usuario_id, descricao, fonte, valor, data_recebimento, recorrente`                               | `data_recebimento` é a data real do dinheiro — **nunca** uma data projetada/esperada. Sustenta o "modo prudente" do cálculo. |
| `gastos`             | `usuario_id, categoria_gasto_id (nullable), obrigacao_fixa_id (nullable), descricao, valor, data` | Lançamento de gasto. Categoria opcional (usuário pode lançar sem categorizar).                                               |
| `movimentos_colchao` | `usuario_id, valor (+/-), tipo (enum), descricao, data`                                           | Livro-razão do colchão — **nunca** um saldo solto no usuário. Saldo atual = `SUM(valor)` por `usuario_id`.                   |

**Ordem de migration importa**: `categorias_gasto` → `obrigacoes_fixas` → `rendas` → `gastos`
(depende das duas anteriores) → `movimentos_colchao`. Os arquivos usam sufixo `_1`/`_2`/`_3` no
timestamp pra forçar essa ordem quando duas migrations caem no mesmo minuto (Laravel ordena por
nome de arquivo dentro do mesmo timestamp) — se criar uma migration nova com dependência,
confirmar a ordem final antes de rodar `migrate`.

`movimentos_colchao.tipo` é `enum('aporte_automatico', 'aporte_manual', 'saque_automatico',
'saque_manual')` — Laravel emite um `CHECK` constraint no Postgres (não um tipo `ENUM` nativo),
então o banco recusa qualquer valor fora da lista (testado ponta a ponta via tinker: inserir tipo
inválido lança `SQLSTATE[23514]: Check violation`). Adicionar um tipo novo exige uma migration
(`ALTER TABLE ... DROP CONSTRAINT ... ADD CONSTRAINT ...` ou recriar a coluna).

`categorias_gasto.icone` guarda o nome do ícone Lucide em kebab-case **sem** o prefixo `lucide`
(ex. `utensils`, `heart-pulse`) — o front monta o nome do componente Angular a partir disso
(`Lucide` + PascalCase). Ver `CategoriaGastoSeeder` pra lista completa.

## Models e padrão de projeto

- **Controller fino, Service com a regra de negócio.** Nenhuma lógica financeira dentro de
  controller — o cálculo de "quanto dá pra gastar essa semana" mora em `SafeToSpendService`
  (`PainelController` só chama), e o fechamento mensal do colchão mora em `CushionService`
  (`FecharMesColchaoCommand` só chama).
- **Todo model de domínio tem `scopeDoUsuario(int $usuarioId)`** (`Renda::doUsuario($id)`,
  idem pros outros 3 — `CategoriaGasto` não tem, é catálogo global). **Todo controller/endpoint
  de domínio precisa filtrar por ele** — nunca listar sem escopo de dono. Isso já é uma correção
  proativa de um gap conhecido do `vitality-Back` (`AlimentoController`, `DietaController` etc.
  não checam dono do recurso hoje).
- **Form Request por ação** (`StoreRendaRequest`, `UpdateGastoRequest`...) — nunca validar inline
  no controller com `$request->validate([...])`.
- **API Resource pra toda resposta** (`RendaResource`, etc.) — nunca devolver o Model cru.
- Relacionamentos: `User::rendas()/gastos()/obrigacoesFixas()/movimentosColchao()` já existem
  (`HasMany`), preferir `auth()->user()->rendas()->create(...)` a montar `usuario_id` manualmente.

## Contrato de rotas

| Rota                                                                      | Observação                                                                                                           |
| ------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `POST /api/login`                                                         | Público. `{email, password}` → `{status, token, user, message}`, 201. 404 e-mail não encontrado, 401 senha errada.   |
| `POST /api/criar-usuario`                                                 | Público. `{name, email, password, password_confirmation}` → `{message, data, success}`, 201.                         |
| `GET /api/user/get-with-token`                                            | Protegida (`auth:sanctum`) — `{data: User}`.                                                                         |
| `POST /api/logout`                                                        | Protegida — revoga o token atual (`currentAccessToken()->delete()`).                                                 |
| `GET/POST/PUT/DELETE /api/rendas`, `/api/gastos`, `/api/obrigacoes-fixas` | Protegidas — CRUD REST padrão (`apiResource`), sempre escopado por `scopeDoUsuario`. `gastos` aceita `?mes=YYYY-MM`. |
| `GET /api/painel/dado-da-semana`                                          | Protegida — `SafeToSpendService::calcular()`, payload em `{data: {...}}` (ver service pros campos).                  |

Leitura de `movimentos_colchao` (extrato) ainda não tem endpoint — só é criado internamente pelo
`CushionService`/`colchao:fechar-mes`. Ver Fase 5+ de `PROXIMAS-FEATURES.md` antes de criar
qualquer rota nova de domínio.

## Decisões de arquitetura

- **Nunca adicionar `EnsureFrontendRequestsAreStateful`** em `bootstrap/app.php` — ver Stack acima.
- **Colchão só é alimentado por fechamento mensal automático** (comando agendado), não a cada
  lançamento de renda — decisão de produto já tomada nesta sessão, não redecidir. Implementado em
  `CushionService::fecharMes()` + `php artisan colchao:fechar-mes` (agendado dia 1, 00:10,
  `America/Sao_Paulo`, ver `routes/console.php`). Parâmetros validados com o usuário (não
  redecidir): linha de base = média simples da renda dos 3 meses anteriores ao mês fechado (sem
  o mês fechado em si); aporte/saque automático = 20% do excedente/déficit sobre essa linha de
  base. Sem histórico nos 3 meses anteriores, o fechamento não faz nada (usuário novo). Saque
  nunca deixa o saldo do colchão negativo.
- **"Modo prudente" é o único modo de cálculo por ora**: nunca projetar renda futura. Se um dia
  entrar um "modo otimista" (projeção), é opt-in explícito do usuário, nunca o padrão.
- **CORS**: `config/cors.php` já existe, restrito a `FRONTEND_URL` (`api/*` apenas,
  `supports_credentials: false` — Bearer token puro não precisa de cookie). Se aparecer erro de
  CORS no front, checar **primeiro** se o backend está rodando (`php artisan serve --port=8001`)
  antes de mexer em `cors.php` — Firefox/Chrome relatam falha de conexão (servidor fora do ar)
  como erro de CORS genérico, o que já confundiu uma vez nesta sessão.

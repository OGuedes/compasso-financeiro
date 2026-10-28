# Compasso — controle financeiro pessoal

Aplicação completa para planejar renda, gastos fixos, compras eventuais, dívidas e meta de economia.

## Baixar para Windows

Baixe o executável portátil na página de [últimos lançamentos](https://github.com/OGuedes/compasso-financeiro/releases/latest). Não é necessário instalar PHP, MySQL, Node.js ou Codex.

## Pré-requisitos

- PHP 8.2 ou superior com `pdo_mysql`
- MySQL 8
- Node.js 20 ou superior e npm

## Banco de dados

Crie as tabelas e, opcionalmente, carregue os dados de demonstração:

```bash
mysql -u root -p < backend/database/schema.sql
mysql -u root -p controle_financeiro < backend/database/seed.sql
```

O schema cria o banco `controle_financeiro` com `utf8mb4`. Se usar outro nome, ajuste o `USE` nos arquivos SQL e o `.env`.

## Backend

```bash
cd backend
copy .env.example .env
php -S localhost:8000 -t public public/index.php
```

Edite `backend/.env` com o host, porta, banco, usuário e senha do MySQL. A API ficará disponível em `http://localhost:8000/api`.

### Testes da API

```bash
php backend/tests/api_test.php
```

A suíte inicia um servidor temporário e usa SQLite somente durante o teste. Ela cobre CRUDs, resumo, cópia de recorrentes, alertas, CORS e validações.

## Frontend

```bash
cd frontend
copy .env.example .env
npm install
npm run dev
```

Abra `http://localhost:5173`. Para validar a versão de produção:

```bash
npm run lint
npm run build
```

## Aplicativo Windows autônomo

O executável portátil inclui Electron, PHP e SQLite. Ele não depende do Codex, MySQL, Node.js ou de um servidor iniciado manualmente.

```bash
cd desktop
npm install
npm run dist
```

O arquivo final é criado em `desktop/dist/Compasso-1.0.0-portable.exe`. Os dados ficam no perfil do usuário, em `%APPDATA%\compasso-desktop\compasso.sqlite`, e permanecem salvos entre execuções.

## Estrutura

```text
backend/
  database/        schema e dados de exemplo
  public/          ponto de entrada HTTP
  routes/          rotas REST
  src/             Config, Controllers, Core, Middleware, Models, Repositories e Services
  tests/           testes HTTP integrados
frontend/
  src/components/  componentes reutilizáveis
  src/context/     mês compartilhado entre telas
  src/pages/       dashboard, gastos, compras, renda, meta e dívidas
  src/services/    cliente Axios
desktop/            empacotamento autônomo para Windows
```

Exemplos de todas as requisições estão em [`requests.http`](requests.http).

## Decisões de implementação

- Rendas são únicas por descrição e mês; isso permite atualizar o salário e ainda cadastrar outras fontes.
- A exclusão de categoria mantém o histórico e define sua referência como `NULL`.
- A cópia de recorrentes ignora nomes já existentes no mês de destino.
- A meta padrão é 20% quando o mês ainda não possui configuração.
- O saldo livre desconta gastos fixos, compras eventuais e a reserva planejada.
- O frontend mantém o mês selecionado em um contexto compartilhado e carrega as páginas sob demanda.
- Dívidas são globais, não ligadas a um único mês; pagamentos e parcelas alimentam o saldo devedor sem duplicar gastos mensais.


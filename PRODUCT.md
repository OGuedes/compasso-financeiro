# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

React 18 com Vite, React Router, Axios e Recharts no frontend; PHP 8.2+ puro e MySQL 8 no backend.

## Users

Pessoas que organizam a própria vida financeira mensalmente e precisam registrar renda, contas fixas, compras eventuais e uma meta de economia em um único lugar.

## Product Purpose

Dar uma visão fiel e acionável do mês: quanto entrou, quanto já foi pago, quanto ainda vence, quanto deve ser reservado e qual saldo realmente permanece disponível.

## Positioning

O saldo disponível já desconta a meta de economia, tratando a reserva como compromisso do mês em vez de sobra eventual.

## Operating Context

O usuário alterna entre uma visão geral do mês e tarefas recorrentes: lançar salário, ajustar a meta, cadastrar contas, marcar pagamentos, copiar recorrências e registrar compras. O mês de referência é o contexto compartilhado entre todas as telas.

## Capabilities and Constraints

- Dashboard mensal com resumo, situação, gastos por categoria e alertas.
- CRUD de gastos fixos e compras eventuais.
- Marcação de contas pagas e cópia de recorrentes para outro mês.
- Cadastro de categorias.
- Renda e meta de economia configuráveis por mês.
- Controle de dívidas com parcelas, valor pago, saldo devedor e quitação.
- Valores exibidos em reais no padrão pt-BR.
- Interface responsiva com estados de carregamento, vazio e erro.
- API REST em JSON disponível localmente em `http://localhost:8000`.

## Evidence on Hand

O briefing funcional é a única fonte de verdade fornecida. Não há marca, logotipo, pesquisas com usuários ou alegações comerciais a representar.

## Product Principles

- Mostrar o valor que ainda exige ação, não apenas totais históricos.
- Manter o mês selecionado consistente em toda a navegação.
- Tornar pagamento e lançamento operações rápidas e reversíveis.
- Explicar estados vazios e erros em linguagem direta.
- Tratar a meta de economia como parte do planejamento mensal.

## Accessibility & Inclusion

Controles devem ser operáveis por teclado, estados não podem depender apenas de cor e gráficos devem ter equivalentes textuais.

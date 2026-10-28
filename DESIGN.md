---
name: Compasso
description: Planejamento mensal claro, humano e acionável.
colors:
  forest: "#12372f"
  action-green: "#1f7a62"
  cream-ground: "#f3efe5"
  paper: "#fffdf8"
  ink: "#18312b"
  muted: "#66766f"
  divider: "#d8d9cf"
  attention-coral: "#d96542"
  progress-gold: "#c69c2f"
typography:
  display:
    fontFamily: "Manrope Variable, sans-serif"
    fontSize: "clamp(2rem, 3.4vw, 3.35rem)"
    fontWeight: 750
    lineHeight: 1.03
    letterSpacing: "-0.035em"
  body:
    fontFamily: "Manrope Variable, sans-serif"
    fontSize: "0.92rem"
    lineHeight: 1.6
  label:
    fontFamily: "Manrope Variable, sans-serif"
    fontSize: "0.72rem"
    fontWeight: 750
rounded:
  control: "10px"
  button: "11px"
  surface: "15px"
spacing:
  compact: "8px"
  standard: "16px"
  section: "24px"
components:
  button-primary:
    backgroundColor: "{colors.action-green}"
    textColor: "{colors.paper}"
    rounded: "{rounded.button}"
    padding: "0 17px"
    height: "42px"
  input:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    height: "42px"
---

# Design System: Compasso

## Overview

**Creative North Star: "Calendário de decisões"**

Compasso combina a precisão de um calendário editorial com o acolhimento de materiais domésticos. O fundo marfim reduz fadiga, o verde profundo organiza a estrutura e cores pontuais comunicam progresso ou atenção.

**Key Characteristics:**

- Hierarquia direta e números tabulares.
- Densidade confortável, com áreas de trabalho claramente separadas.
- Ação em verde, atenção em coral e progresso em dourado.
- Uma trilha mensal transforma totais em sequência compreensível.

## Colors

O verde ancora a navegação e as ações; marfim e papel mantêm longas sessões confortáveis; coral só aparece quando há algo que exige atenção.

**The Meaningful Color Rule.** Cores saturadas sempre carregam ação, estado ou série de dados.

## Typography

Manrope Variable serve display, corpo e rótulos. Títulos usam peso forte e espaçamento compacto; textos de apoio permanecem curtos e arejados. Valores usam numerais tabulares.

## Layout

A aplicação usa navegação lateral fixa em telas amplas e barra inferior com cinco destinos no mobile. O conteúdo tem largura fluida, cartões de trabalho usam 24px de preenchimento e os grids colapsam para uma coluna abaixo de 860px.

## Elevation & Depth

Superfícies principais usam papel sobre fundo marfim e uma sombra ambiente baixa (`0 14px 36px -22px rgba(18,55,47,.45)`). Divisores simples organizam métricas e tabelas sem empilhar cartões.

## Shapes

Controles usam cantos de 10–11px; superfícies usam 15–16px. Pílulas ficam restritas a estados compactos e contadores.

## Components

### Buttons

Botões primários são verdes, brancos e firmes; o hover sobe 1px. Secundários usam papel e contorno discreto. Todo foco recebe anel verde visível.

### Cards / Containers

Superfícies recebem papel, cantos médios e sombra ambiente. Tabelas e faixas de métricas preferem divisores a cartões aninhados.

### Inputs / Fields

Campos têm fundo quase branco, borda neutra e foco com borda verde mais halo translúcido. Rótulos sempre aparecem acima do controle.

### Navigation

A lateral verde profundo usa seleção dourada. No mobile, a barra inferior mantém os quatro destinos principais ao alcance do polegar.

### Trilha do mês

Quatro etapas — entrada, compromissos, reserva e saldo livre — combinam número, rótulo e barra proporcional em uma única leitura.

## Do's and Don'ts

### Do:

- **Do** manter o mês de referência visível em todas as telas.
- **Do** combinar cor com texto explícito nos estados.
- **Do** reservar o coral para pendências e riscos reais.

### Don't:

- **Don't** usar cor decorativa sem significado.
- **Don't** esconder ações essenciais em menus no mobile.
- **Don't** substituir tabelas e formulários por metáforas visuais.


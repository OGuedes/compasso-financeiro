const STORAGE_KEY = 'compasso:finance-data:v1'

const emptyState = () => ({
  nextId: 1,
  categories: [],
  incomes: [],
  expenses: [],
  purchases: [],
  savingsGoals: [],
  debts: [],
})

function readState() {
  try {
    return { ...emptyState(), ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') }
  } catch {
    return emptyState()
  }
}

function saveState(state) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
}

function response(data) {
  return Promise.resolve({ data: { data } })
}

function failure(message) {
  const error = new Error(message)
  error.userMessage = message
  return Promise.reject(error)
}

function create(state, collection, values) {
  const item = { id: state.nextId++, ...values }
  state[collection].push(item)
  saveState(state)
  return item
}

function categoryName(state, categoryId) {
  return state.categories.find((item) => item.id === Number(categoryId))?.nome || null
}

function presentDebt(debt) {
  const total = Number(debt.valor_total)
  const paid = Number(debt.valor_pago)
  const remaining = Math.max(0, total - paid)
  return {
    ...debt,
    saldo_devedor: Number(remaining.toFixed(2)),
    valor_parcela: Number((total / Math.max(1, Number(debt.total_parcelas))).toFixed(2)),
    percentual_pago: total ? Number(Math.min(100, paid / total * 100).toFixed(1)) : 0,
    situacao: remaining <= 0 || Number(debt.parcelas_pagas) >= Number(debt.total_parcelas) ? 'quitada' : 'aberta',
  }
}

function summary(state, month) {
  const incomes = state.incomes.filter((item) => item.mes_referencia === month)
  const expenses = state.expenses.filter((item) => item.mes_referencia === month)
  const purchases = state.purchases.filter((item) => item.data?.startsWith(month))
  const salary = incomes.reduce((sum, item) => sum + Number(item.valor), 0)
  const fixed = expenses.reduce((sum, item) => sum + Number(item.valor), 0)
  const paid = expenses.filter((item) => Number(item.pago)).reduce((sum, item) => sum + Number(item.valor), 0)
  const purchasesTotal = purchases.reduce((sum, item) => sum + Number(item.valor), 0)
  const percentage = Number(state.savingsGoals.find((item) => item.mes_referencia === month)?.percentual ?? 20)
  const goal = Number((salary * percentage / 100).toFixed(2))
  const balance = Number((salary - fixed - purchasesTotal - goal).toFixed(2))
  const grouped = new Map()

  for (const item of [...expenses, ...purchases]) {
    const name = categoryName(state, item.categoria_id) || 'Sem categoria'
    grouped.set(name, (grouped.get(name) || 0) + Number(item.valor))
  }

  return {
    mes_referencia: month,
    salario_total: salary,
    total_gastos_fixos: fixed,
    total_pago: paid,
    total_a_pagar: fixed - paid,
    total_compras_eventuais: purchasesTotal,
    percentual_meta: percentage,
    valor_meta_economia: goal,
    saldo_disponivel: balance,
    gastos_por_categoria: [...grouped].map(([categoria, total]) => ({ categoria, total })),
    situacao: balance >= 0 ? 'ok' : 'estourado',
  }
}

function alerts(state) {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return state.expenses.flatMap((item) => {
    if (Number(item.pago) || !item.dia_vencimento) return []
    const [year, month] = item.mes_referencia.split('-').map(Number)
    const due = new Date(year, month - 1, Number(item.dia_vencimento))
    const days = Math.round((due - today) / 86400000)
    return days >= 0 && days <= 5 ? [{ ...item, dias_restantes: days }] : []
  }).sort((a, b) => a.dias_restantes - b.dias_restantes)
}

function normalizeExpense(state, payload, current = {}) {
  if (!payload.nome?.trim() || !(Number(payload.valor) > 0)) throw new Error('Informe o nome e um valor maior que zero.')
  return {
    ...current,
    ...payload,
    nome: payload.nome.trim(),
    valor: Number(payload.valor),
    pago: Number(payload.pago ?? current.pago ?? 0),
    categoria_id: payload.categoria_id ? Number(payload.categoria_id) : null,
    categoria_nome: categoryName(state, payload.categoria_id),
    dia_vencimento: payload.dia_vencimento ? Number(payload.dia_vencimento) : null,
    recorrente: Number(Boolean(payload.recorrente)),
  }
}

function normalizePurchase(state, payload, current = {}) {
  if (!payload.nome?.trim() || !(Number(payload.valor) > 0) || !payload.data) throw new Error('Preencha descrição, valor e data.')
  return {
    ...current,
    ...payload,
    nome: payload.nome.trim(),
    valor: Number(payload.valor),
    categoria_id: payload.categoria_id ? Number(payload.categoria_id) : null,
    categoria_nome: categoryName(state, payload.categoria_id),
    observacao: payload.observacao?.trim() || null,
  }
}

function normalizeDebt(payload, current = {}) {
  const total = Number(payload.valor_total)
  const installments = Number(payload.total_parcelas)
  const paidInstallments = Number(payload.parcelas_pagas || 0)
  const paid = Number(payload.valor_pago || 0)
  if (!payload.nome?.trim() || !(total > 0) || installments < 1 || paidInstallments > installments || paid > total) {
    throw new Error('Revise o nome, os valores e a quantidade de parcelas.')
  }
  return {
    ...current,
    ...payload,
    nome: payload.nome.trim(),
    credor: payload.credor?.trim() || null,
    valor_total: total,
    total_parcelas: installments,
    parcelas_pagas: paidInstallments,
    valor_pago: paid,
    dia_vencimento: payload.dia_vencimento ? Number(payload.dia_vencimento) : null,
    observacao: payload.observacao?.trim() || null,
  }
}

async function get(url, config = {}) {
  const state = readState()
  const params = config.params || {}
  if (url === '/categories') return response(state.categories)
  if (url === '/incomes') return response(state.incomes.filter((item) => item.mes_referencia === params.mes))
  if (url === '/savings-goal') return response(state.savingsGoals.find((item) => item.mes_referencia === params.mes) || { id: null, mes_referencia: params.mes, percentual: 20, padrao: true })
  if (url === '/summary') return response(summary(state, params.mes))
  if (url === '/alerts') return response(alerts(state))
  if (url === '/expenses') {
    const direction = params.order === 'desc' ? -1 : 1
    const key = params.sort === 'nome' ? 'nome' : 'valor'
    const rows = state.expenses.filter((item) => item.mes_referencia === params.mes).map((item) => ({ ...item, categoria_nome: categoryName(state, item.categoria_id) }))
    rows.sort((a, b) => direction * (key === 'nome' ? a.nome.localeCompare(b.nome, 'pt-BR') : Number(a.valor) - Number(b.valor)))
    return response(rows)
  }
  if (url === '/purchases') return response(state.purchases.filter((item) => item.data?.startsWith(params.mes)).map((item) => ({ ...item, categoria_nome: categoryName(state, item.categoria_id) })).sort((a, b) => b.data.localeCompare(a.data)))
  if (url === '/debts') {
    const rows = state.debts.map(presentDebt).filter((item) => params.status === 'open' ? item.situacao === 'aberta' : params.status === 'paid' ? item.situacao === 'quitada' : true)
    return response(rows)
  }
  return failure('Recurso não encontrado.')
}

async function post(url, payload) {
  const state = readState()
  try {
    if (url === '/categories') {
      const name = payload.nome?.trim()
      if (!name) return failure('Informe o nome da categoria.')
      if (state.categories.some((item) => item.nome.toLocaleLowerCase('pt-BR') === name.toLocaleLowerCase('pt-BR'))) return failure('Essa categoria já existe.')
      return response(create(state, 'categories', { nome: name }))
    }
    if (url === '/incomes') {
      const current = state.incomes.find((item) => item.mes_referencia === payload.mes_referencia)
      if (current) Object.assign(current, { descricao: payload.descricao || 'Salário', valor: Number(payload.valor) })
      else create(state, 'incomes', { descricao: payload.descricao || 'Salário', valor: Number(payload.valor), mes_referencia: payload.mes_referencia })
      saveState(state)
      return response(current || state.incomes.at(-1))
    }
    if (url === '/expenses') return response(create(state, 'expenses', normalizeExpense(state, payload)))
    if (url === '/purchases') return response(create(state, 'purchases', normalizePurchase(state, payload)))
    if (url === '/debts') return response(presentDebt(create(state, 'debts', normalizeDebt(payload))))
    if (url === '/expenses/copy') {
      let copied = 0
      for (const item of state.expenses.filter((expense) => expense.mes_referencia === payload.de && Number(expense.recorrente))) {
        const exists = state.expenses.some((expense) => expense.mes_referencia === payload.para && expense.nome === item.nome)
        if (!exists) {
          const copy = { ...item }
          delete copy.id
          create(state, 'expenses', { ...copy, pago: 0, mes_referencia: payload.para })
          copied++
        }
      }
      saveState(state)
      return response({ copiados: copied, de: payload.de, para: payload.para })
    }
  } catch (error) {
    return failure(error.message)
  }
  return failure('Recurso não encontrado.')
}

async function put(url, payload) {
  const state = readState()
  try {
    if (url === '/savings-goal') {
      const percentage = Number(payload.percentual)
      if (percentage < 0 || percentage > 100) return failure('Informe um percentual entre 0 e 100.')
      let goal = state.savingsGoals.find((item) => item.mes_referencia === payload.mes_referencia)
      if (goal) goal.percentual = percentage
      else goal = create(state, 'savingsGoals', { mes_referencia: payload.mes_referencia, percentual: percentage })
      saveState(state)
      return response(goal)
    }
    const match = url.match(/^\/(expenses|purchases|debts)\/(\d+)$/)
    if (!match) return failure('Recurso não encontrado.')
    const [, collection, rawId] = match
    const item = state[collection].find((entry) => entry.id === Number(rawId))
    if (!item) return failure('Registro não encontrado.')
    const normalized = collection === 'expenses' ? normalizeExpense(state, payload, item) : collection === 'purchases' ? normalizePurchase(state, payload, item) : normalizeDebt(payload, item)
    Object.assign(item, normalized)
    saveState(state)
    return response(collection === 'debts' ? presentDebt(item) : item)
  } catch (error) {
    return failure(error.message)
  }
}

async function patch(url) {
  const state = readState()
  const expense = url.match(/^\/expenses\/(\d+)\/pay$/)
  if (expense) {
    const item = state.expenses.find((entry) => entry.id === Number(expense[1]))
    if (!item) return failure('Gasto não encontrado.')
    item.pago = Number(!Number(item.pago)); saveState(state); return response(item)
  }
  const debt = url.match(/^\/debts\/(\d+)\/pay-installment$/)
  if (debt) {
    const item = state.debts.find((entry) => entry.id === Number(debt[1]))
    if (!item) return failure('Dívida não encontrada.')
    const presented = presentDebt(item)
    if (presented.situacao === 'quitada') return failure('Esta dívida já está quitada.')
    item.parcelas_pagas = Number(item.parcelas_pagas) + 1
    item.valor_pago = item.parcelas_pagas >= Number(item.total_parcelas) ? Number(item.valor_total) : Math.min(Number(item.valor_total), Number(item.valor_pago) + presented.valor_parcela)
    saveState(state); return response(presentDebt(item))
  }
  return failure('Recurso não encontrado.')
}

async function remove(url) {
  const state = readState()
  const match = url.match(/^\/(expenses|purchases|debts)\/(\d+)$/)
  if (!match) return failure('Recurso não encontrado.')
  const [, collection, rawId] = match
  const before = state[collection].length
  state[collection] = state[collection].filter((item) => item.id !== Number(rawId))
  if (state[collection].length === before) return failure('Registro não encontrado.')
  saveState(state)
  return response(null)
}

export default { get, post, put, patch, delete: remove }

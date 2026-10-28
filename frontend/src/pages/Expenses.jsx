import { useCallback, useEffect, useMemo, useState } from 'react'
import { ArrowDownAZ, ArrowUpAZ, Check, Copy, Pencil, Plus, Trash2, Undo2 } from 'lucide-react'
import CategoryField from '../components/CategoryField'
import { EmptyState, ErrorState, LoadingState } from '../components/Feedback'
import PageHeader from '../components/PageHeader'
import { useMonth } from '../context/MonthContext'
import api from '../services/api'
import { money } from '../utils/format'

const initialForm = { nome: '', valor: '', dia_vencimento: '', categoria_id: '', recorrente: true }

export default function Expenses() {
  const { month } = useMonth()
  const [expenses, setExpenses] = useState([])
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState(initialForm)
  const [editingId, setEditingId] = useState(null)
  const [sort, setSort] = useState('valor')
  const [order, setOrder] = useState('asc')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const load = useCallback(async () => {
    setLoading(true); setError('')
    try {
      const [expensesResponse, categoriesResponse] = await Promise.all([
        api.get('/expenses', { params: { mes: month, sort, order } }),
        api.get('/categories'),
      ])
      setExpenses(expensesResponse.data.data)
      setCategories(categoriesResponse.data.data)
    } catch (requestError) { setError(requestError.userMessage) }
    finally { setLoading(false) }
  }, [month, sort, order])

  useEffect(() => { load() }, [load])

  const totals = useMemo(() => ({
    total: expenses.reduce((sum, item) => sum + Number(item.valor), 0),
    paid: expenses.filter((item) => Number(item.pago)).reduce((sum, item) => sum + Number(item.valor), 0),
  }), [expenses])

  function updateField(field, value) { setForm((current) => ({ ...current, [field]: value })) }
  function resetForm() { setForm(initialForm); setEditingId(null); setError('') }

  async function submit(event) {
    event.preventDefault(); setSaving(true); setError(''); setNotice('')
    const payload = { ...form, valor: Number(form.valor), mes_referencia: month, dia_vencimento: form.dia_vencimento || null, categoria_id: form.categoria_id || null }
    try {
      if (editingId) await api.put(`/expenses/${editingId}`, payload)
      else await api.post('/expenses', payload)
      setNotice(editingId ? 'Gasto atualizado.' : 'Gasto adicionado ao mês.')
      resetForm(); await load()
    } catch (requestError) { setError(requestError.userMessage) }
    finally { setSaving(false) }
  }

  function edit(expense) {
    setEditingId(expense.id)
    setForm({ nome: expense.nome, valor: expense.valor, dia_vencimento: expense.dia_vencimento || '', categoria_id: expense.categoria_id || '', recorrente: Boolean(Number(expense.recorrente)) })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  async function togglePaid(id) {
    try { await api.patch(`/expenses/${id}/pay`); await load() }
    catch (requestError) { setError(requestError.userMessage) }
  }

  async function remove(id) {
    if (!window.confirm('Excluir este gasto fixo?')) return
    try { await api.delete(`/expenses/${id}`); setNotice('Gasto excluído.'); await load() }
    catch (requestError) { setError(requestError.userMessage) }
  }

  async function copyToNextMonth() {
    const [year, monthNumber] = month.split('-').map(Number)
    const next = new Date(year, monthNumber, 1)
    const target = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`
    try {
      const { data } = await api.post('/expenses/copy', { de: month, para: target })
      setNotice(`${data.data.copiados} gasto(s) recorrente(s) copiado(s) para ${target}.`)
    } catch (requestError) { setError(requestError.userMessage) }
  }

  return (
    <>
      <PageHeader title="Gastos fixos" description="Organize os compromissos que estruturam o mês e marque o que já foi pago." action={<button className="button secondary" onClick={copyToNextMonth}><Copy size={17} /> Copiar para o próximo mês</button>} />
      {notice && <div className="notice" role="status"><Check size={17} />{notice}</div>}
      {error && <ErrorState message={error} onRetry={load} />}

      <div className="management-layout">
        <section className="form-panel" aria-labelledby="expense-form-title">
          <div className="panel-heading"><div><h2 id="expense-form-title">{editingId ? 'Editar compromisso' : 'Novo compromisso'}</h2><p>Os campos com * são obrigatórios.</p></div></div>
          <form onSubmit={submit}>
            <label><span>Nome *</span><input required maxLength="150" value={form.nome} onChange={(event) => updateField('nome', event.target.value)} placeholder="Ex.: Internet" /></label>
            <div className="form-row">
              <label><span>Valor *</span><input required min="0.01" step="0.01" type="number" value={form.valor} onChange={(event) => updateField('valor', event.target.value)} placeholder="0,00" /></label>
              <label><span>Vencimento</span><input min="1" max="31" type="number" value={form.dia_vencimento} onChange={(event) => updateField('dia_vencimento', event.target.value)} placeholder="Dia" /></label>
            </div>
            <label><span>Categoria</span><CategoryField categories={categories} setCategories={setCategories} value={String(form.categoria_id)} onChange={(value) => updateField('categoria_id', value)} /></label>
            <label className="check-field"><input type="checkbox" checked={form.recorrente} onChange={(event) => updateField('recorrente', event.target.checked)} /><span><strong>Gasto recorrente</strong><small>Poderá ser copiado para o próximo mês.</small></span></label>
            <div className="form-actions">
              {editingId && <button type="button" className="button ghost" onClick={resetForm}><Undo2 size={17} /> Cancelar</button>}
              <button className="button primary" disabled={saving}>{saving ? 'Salvando…' : <><Plus size={17} /> {editingId ? 'Salvar alterações' : 'Adicionar gasto'}</>}</button>
            </div>
          </form>
        </section>

        <section className="list-panel" aria-labelledby="expenses-list-title">
          <div className="list-toolbar">
            <div><h2 id="expenses-list-title">Compromissos do mês</h2><p>{money(totals.paid)} pagos de {money(totals.total)}</p></div>
            <div className="sort-controls">
              <select aria-label="Ordenar gastos por" value={sort} onChange={(event) => setSort(event.target.value)}><option value="valor">Valor</option><option value="nome">Nome</option></select>
              <button className="icon-button" onClick={() => setOrder((current) => current === 'asc' ? 'desc' : 'asc')} aria-label={`Ordem ${order === 'asc' ? 'crescente' : 'decrescente'}`}>{order === 'asc' ? <ArrowDownAZ size={19} /> : <ArrowUpAZ size={19} />}</button>
            </div>
          </div>
          {loading ? <LoadingState /> : expenses.length === 0 ? <EmptyState title="Nenhum gasto fixo" description="Adicione o primeiro compromisso deste mês pelo formulário." /> : (
            <div className="data-table-wrap">
              <table className="data-table">
                <thead><tr><th>Status</th><th>Compromisso</th><th>Vence</th><th>Valor</th><th><span className="sr-only">Ações</span></th></tr></thead>
                <tbody>{expenses.map((expense) => (
                  <tr key={expense.id} className={Number(expense.pago) ? 'is-paid' : ''}>
                    <td><button className={`pay-toggle ${Number(expense.pago) ? 'paid' : ''}`} onClick={() => togglePaid(expense.id)} aria-label={Number(expense.pago) ? `Marcar ${expense.nome} como não pago` : `Marcar ${expense.nome} como pago`}><Check size={15} /></button></td>
                    <td data-label="Compromisso"><strong>{expense.nome}</strong><small>{expense.categoria_nome || 'Sem categoria'}{Number(expense.recorrente) ? ' · Recorrente' : ''}</small></td>
                    <td data-label="Vence">{expense.dia_vencimento ? `Dia ${expense.dia_vencimento}` : '—'}</td>
                    <td data-label="Valor" className="money-cell">{money(expense.valor)}</td>
                    <td className="row-actions"><button className="icon-button" onClick={() => edit(expense)} aria-label={`Editar ${expense.nome}`}><Pencil size={17} /></button><button className="icon-button danger" onClick={() => remove(expense.id)} aria-label={`Excluir ${expense.nome}`}><Trash2 size={17} /></button></td>
                  </tr>
                ))}</tbody>
              </table>
            </div>
          )}
        </section>
      </div>
    </>
  )
}

import { useCallback, useEffect, useMemo, useState } from 'react'
import { CalendarDays, Check, Pencil, Plus, Trash2, Undo2 } from 'lucide-react'
import CategoryField from '../components/CategoryField'
import { EmptyState, ErrorState, LoadingState } from '../components/Feedback'
import PageHeader from '../components/PageHeader'
import { useMonth } from '../context/MonthContext'
import api from '../services/api'
import { dateLabel, money } from '../utils/format'

function defaultDate(month) {
  const today = new Date()
  const current = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
  return current === month ? `${current}-${String(today.getDate()).padStart(2, '0')}` : `${month}-01`
}

export default function Purchases() {
  const { month } = useMonth()
  const blank = useMemo(() => ({ nome: '', valor: '', data: defaultDate(month), categoria_id: '', observacao: '' }), [month])
  const [purchases, setPurchases] = useState([])
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState(blank)
  const [editingId, setEditingId] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const load = useCallback(async () => {
    setLoading(true); setError('')
    try {
      const [purchaseResponse, categoryResponse] = await Promise.all([api.get('/purchases', { params: { mes: month } }), api.get('/categories')])
      setPurchases(purchaseResponse.data.data); setCategories(categoryResponse.data.data)
    } catch (requestError) { setError(requestError.userMessage) }
    finally { setLoading(false) }
  }, [month])

  useEffect(() => { setForm(blank); setEditingId(null); load() }, [blank, load])

  function updateField(field, value) { setForm((current) => ({ ...current, [field]: value })) }
  function resetForm() { setForm(blank); setEditingId(null) }

  async function submit(event) {
    event.preventDefault(); setSaving(true); setError(''); setNotice('')
    const payload = { ...form, valor: Number(form.valor), categoria_id: form.categoria_id || null }
    try {
      if (editingId) await api.put(`/purchases/${editingId}`, payload)
      else await api.post('/purchases', payload)
      setNotice(editingId ? 'Compra atualizada.' : 'Compra registrada.')
      resetForm(); await load()
    } catch (requestError) { setError(requestError.userMessage) }
    finally { setSaving(false) }
  }

  function edit(purchase) {
    setEditingId(purchase.id)
    setForm({ nome: purchase.nome, valor: purchase.valor, data: purchase.data, categoria_id: purchase.categoria_id || '', observacao: purchase.observacao || '' })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  async function remove(id) {
    if (!window.confirm('Excluir esta compra?')) return
    try { await api.delete(`/purchases/${id}`); setNotice('Compra excluída.'); await load() }
    catch (requestError) { setError(requestError.userMessage) }
  }

  const total = purchases.reduce((sum, item) => sum + Number(item.valor), 0)

  return (
    <>
      <PageHeader title="Compras eventuais" description="Registre os gastos que acontecem fora dos compromissos recorrentes." />
      {notice && <div className="notice" role="status"><Check size={17} />{notice}</div>}
      {error && <ErrorState message={error} onRetry={load} />}
      <div className="management-layout">
        <section className="form-panel" aria-labelledby="purchase-form-title">
          <div className="panel-heading"><div><h2 id="purchase-form-title">{editingId ? 'Editar compra' : 'Nova compra'}</h2><p>Registre no dia em que o gasto aconteceu.</p></div></div>
          <form onSubmit={submit}>
            <label><span>Descrição *</span><input required maxLength="150" value={form.nome} onChange={(event) => updateField('nome', event.target.value)} placeholder="Ex.: Supermercado" /></label>
            <div className="form-row">
              <label><span>Valor *</span><input required min="0.01" step="0.01" type="number" value={form.valor} onChange={(event) => updateField('valor', event.target.value)} placeholder="0,00" /></label>
              <label><span>Data *</span><input required type="date" value={form.data} onChange={(event) => updateField('data', event.target.value)} /></label>
            </div>
            <label><span>Categoria</span><CategoryField categories={categories} setCategories={setCategories} value={String(form.categoria_id)} onChange={(value) => updateField('categoria_id', value)} /></label>
            <label><span>Observação</span><textarea rows="3" maxLength="1000" value={form.observacao} onChange={(event) => updateField('observacao', event.target.value)} placeholder="Detalhes opcionais" /></label>
            <div className="form-actions">
              {editingId && <button type="button" className="button ghost" onClick={resetForm}><Undo2 size={17} /> Cancelar</button>}
              <button className="button primary" disabled={saving}>{saving ? 'Salvando…' : <><Plus size={17} /> {editingId ? 'Salvar alterações' : 'Registrar compra'}</>}</button>
            </div>
          </form>
        </section>

        <section className="list-panel" aria-labelledby="purchases-list-title">
          <div className="list-toolbar"><div><h2 id="purchases-list-title">Compras do mês</h2><p>{purchases.length} registro(s) · {money(total)}</p></div></div>
          {loading ? <LoadingState /> : purchases.length === 0 ? <EmptyState title="Nenhuma compra registrada" description="Use o formulário para registrar o primeiro gasto eventual." /> : (
            <ul className="purchase-list">
              {purchases.map((purchase) => (
                <li key={purchase.id}>
                  <span className="purchase-date"><CalendarDays size={17} /><span>{dateLabel(purchase.data)}</span></span>
                  <div className="purchase-info"><strong>{purchase.nome}</strong><small>{purchase.categoria_nome || 'Sem categoria'}{purchase.observacao ? ` · ${purchase.observacao}` : ''}</small></div>
                  <strong className="purchase-value">{money(purchase.valor)}</strong>
                  <div className="row-actions"><button className="icon-button" onClick={() => edit(purchase)} aria-label={`Editar ${purchase.nome}`}><Pencil size={17} /></button><button className="icon-button danger" onClick={() => remove(purchase.id)} aria-label={`Excluir ${purchase.nome}`}><Trash2 size={17} /></button></div>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </>
  )
}

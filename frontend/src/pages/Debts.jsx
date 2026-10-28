import { useCallback, useEffect, useMemo, useState } from 'react'
import { Check, CircleDollarSign, HandCoins, Pencil, Plus, Trash2, Undo2 } from 'lucide-react'
import { EmptyState, ErrorState, LoadingState } from '../components/Feedback'
import PageHeader from '../components/PageHeader'
import api from '../services/api'
import { money } from '../utils/format'

const initialForm = {
  nome: '', credor: '', valor_total: '', total_parcelas: 1,
  parcelas_pagas: 0, valor_pago: 0, dia_vencimento: '', observacao: '',
}

export default function Debts() {
  const [debts, setDebts] = useState([])
  const [form, setForm] = useState(initialForm)
  const [editingId, setEditingId] = useState(null)
  const [status, setStatus] = useState('all')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const load = useCallback(async () => {
    setLoading(true)
    setError('')
    try {
      const response = await api.get('/debts', { params: { status } })
      setDebts(response.data.data)
    } catch (requestError) {
      setError(requestError.userMessage)
    } finally {
      setLoading(false)
    }
  }, [status])

  useEffect(() => { load() }, [load])

  const summary = useMemo(() => debts.reduce((totals, debt) => ({
    total: totals.total + Number(debt.valor_total),
    paid: totals.paid + Number(debt.valor_pago),
    remaining: totals.remaining + Number(debt.saldo_devedor),
    open: totals.open + (debt.situacao === 'aberta' ? 1 : 0),
  }), { total: 0, paid: 0, remaining: 0, open: 0 }), [debts])

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  function resetForm() {
    setForm(initialForm)
    setEditingId(null)
    setError('')
  }

  async function submit(event) {
    event.preventDefault()
    setSaving(true)
    setError('')
    setNotice('')
    const payload = {
      ...form,
      valor_total: Number(form.valor_total),
      total_parcelas: Number(form.total_parcelas),
      parcelas_pagas: Number(form.parcelas_pagas),
      valor_pago: Number(form.valor_pago),
      dia_vencimento: form.dia_vencimento || null,
    }
    try {
      if (editingId !== null) await api.put(`/debts/${editingId}`, payload)
      else await api.post('/debts', payload)
      setNotice(editingId !== null ? 'Dívida atualizada.' : 'Dívida adicionada ao controle.')
      resetForm()
      await load()
    } catch (requestError) {
      setError(requestError.userMessage)
    } finally {
      setSaving(false)
    }
  }

  function edit(debt) {
    setEditingId(debt.id)
    setForm({
      nome: debt.nome,
      credor: debt.credor || '',
      valor_total: debt.valor_total,
      total_parcelas: debt.total_parcelas,
      parcelas_pagas: debt.parcelas_pagas,
      valor_pago: debt.valor_pago,
      dia_vencimento: debt.dia_vencimento || '',
      observacao: debt.observacao || '',
    })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  async function payInstallment(debt) {
    try {
      await api.patch(`/debts/${debt.id}/pay-installment`)
      setNotice(`Parcela de ${debt.nome} registrada como paga.`)
      await load()
    } catch (requestError) {
      setError(requestError.userMessage)
    }
  }

  async function remove(debt) {
    if (!window.confirm(`Excluir a dívida “${debt.nome}”?`)) return
    try {
      await api.delete(`/debts/${debt.id}`)
      setNotice('Dívida excluída.')
      await load()
    } catch (requestError) {
      setError(requestError.userMessage)
    }
  }

  return (
    <>
      <PageHeader title="Dívidas" description="Acompanhe parcelas, valores pagos e o saldo que ainda falta quitar. Este controle independe do mês selecionado." />
      {notice ? <div className="notice" role="status"><Check size={17} />{notice}</div> : null}
      {error ? <ErrorState message={error} onRetry={load} /> : null}

      <section className="debt-summary" aria-label="Resumo das dívidas exibidas">
        <div><span>Valor contratado</span><strong>{money(summary.total)}</strong></div>
        <div><span>Já pago</span><strong>{money(summary.paid)}</strong></div>
        <div className="remaining"><span>Saldo devedor</span><strong>{money(summary.remaining)}</strong></div>
        <div><span>Em aberto</span><strong>{summary.open}</strong></div>
      </section>

      <div className="management-layout debts-layout">
        <section className="form-panel" aria-labelledby="debt-form-title">
          <div className="panel-heading"><div><h2 id="debt-form-title">{editingId !== null ? 'Editar dívida' : 'Nova dívida'}</h2><p>Informe o acordo e o progresso atual.</p></div></div>
          <form onSubmit={submit}>
            <label><span>Nome da dívida *</span><input required maxLength="150" value={form.nome} onChange={(event) => updateField('nome', event.target.value)} placeholder="Ex.: Empréstimo pessoal" /></label>
            <label><span>Credor</span><input maxLength="150" value={form.credor} onChange={(event) => updateField('credor', event.target.value)} placeholder="Banco, loja ou pessoa" /></label>
            <div className="form-row">
              <label><span>Valor total *</span><input required min="0.01" step="0.01" type="number" value={form.valor_total} onChange={(event) => updateField('valor_total', event.target.value)} placeholder="0,00" /></label>
              <label><span>Total de parcelas *</span><input required min="1" max="600" type="number" value={form.total_parcelas} onChange={(event) => updateField('total_parcelas', event.target.value)} /></label>
            </div>
            <div className="form-row">
              <label><span>Parcelas pagas</span><input min="0" max={form.total_parcelas || 600} type="number" value={form.parcelas_pagas} onChange={(event) => updateField('parcelas_pagas', event.target.value)} /></label>
              <label><span>Valor já pago</span><input min="0" max={form.valor_total || undefined} step="0.01" type="number" value={form.valor_pago} onChange={(event) => updateField('valor_pago', event.target.value)} /></label>
            </div>
            <label><span>Dia do vencimento</span><input min="1" max="31" type="number" value={form.dia_vencimento} onChange={(event) => updateField('dia_vencimento', event.target.value)} placeholder="Dia" /></label>
            <label><span>Observação</span><textarea rows="3" maxLength="1000" value={form.observacao} onChange={(event) => updateField('observacao', event.target.value)} placeholder="Taxa, acordo ou outro detalhe" /></label>
            <div className="form-actions">
              {editingId !== null ? <button type="button" className="button ghost" onClick={resetForm}><Undo2 size={17} /> Cancelar</button> : null}
              <button className="button primary" disabled={saving}>{saving ? 'Salvando…' : <><Plus size={17} /> {editingId !== null ? 'Salvar alterações' : 'Adicionar dívida'}</>}</button>
            </div>
          </form>
        </section>

        <section className="list-panel" aria-labelledby="debts-list-title">
          <div className="list-toolbar debt-toolbar">
            <div><h2 id="debts-list-title">Acompanhamento</h2><p>Valores calculados a partir dos registros.</p></div>
            <div className="segmented-control" aria-label="Filtrar dívidas">
              {[['all', 'Todas'], ['open', 'Em aberto'], ['paid', 'Quitadas']].map(([value, label]) => (
                <button key={value} className={status === value ? 'active' : ''} onClick={() => setStatus(value)} aria-pressed={status === value}>{label}</button>
              ))}
            </div>
          </div>

          {loading ? <LoadingState /> : debts.length === 0 ? (
            <EmptyState title="Nenhuma dívida neste filtro" description="Adicione uma dívida ou escolha outro estado para acompanhar." />
          ) : (
            <ul className="debt-list">
              {debts.map((debt) => (
                <li key={debt.id} className={debt.situacao === 'quitada' ? 'is-settled' : ''}>
                  <div className="debt-heading">
                    <span className="debt-icon">{debt.situacao === 'quitada' ? <Check size={20} /> : <HandCoins size={20} />}</span>
                    <div><strong>{debt.nome}</strong><small>{debt.credor || 'Credor não informado'}{debt.dia_vencimento ? ` · vence dia ${debt.dia_vencimento}` : ''}</small></div>
                    <span className={`status ${debt.situacao === 'quitada' ? 'ok' : 'open'}`}>{debt.situacao === 'quitada' ? 'Quitada' : 'Em aberto'}</span>
                  </div>
                  <div className="debt-values">
                    <div><span>Saldo devedor</span><strong>{money(debt.saldo_devedor)}</strong></div>
                    <div><span>Já pago</span><strong>{money(debt.valor_pago)}</strong></div>
                    <div><span>Parcelas</span><strong>{debt.parcelas_pagas}/{debt.total_parcelas}</strong></div>
                    <div><span>Parcela média</span><strong>{money(debt.valor_parcela)}</strong></div>
                  </div>
                  <div className="debt-progress-row">
                    <progress max="100" value={debt.percentual_pago} aria-label={`${debt.percentual_pago}% da dívida ${debt.nome} paga`} />
                    <span>{debt.percentual_pago}% pago</span>
                  </div>
                  {debt.observacao ? <p className="debt-note">{debt.observacao}</p> : null}
                  <div className="debt-actions">
                    {debt.situacao === 'aberta' ? <button className="button secondary compact" onClick={() => payInstallment(debt)}><CircleDollarSign size={16} /> Pagar uma parcela</button> : null}
                    <button className="icon-button" onClick={() => edit(debt)} aria-label={`Editar ${debt.nome}`}><Pencil size={17} /></button>
                    <button className="icon-button danger" onClick={() => remove(debt)} aria-label={`Excluir ${debt.nome}`}><Trash2 size={17} /></button>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>
    </>
  )
}

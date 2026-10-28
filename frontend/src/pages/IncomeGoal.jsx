import { useCallback, useEffect, useMemo, useState } from 'react'
import { Check, Info, Landmark, PiggyBank, Save } from 'lucide-react'
import { ErrorState, LoadingState } from '../components/Feedback'
import PageHeader from '../components/PageHeader'
import { useMonth } from '../context/MonthContext'
import api from '../services/api'
import { money, monthLabel } from '../utils/format'

export default function IncomeGoal() {
  const { month } = useMonth()
  const [income, setIncome] = useState('')
  const [percentage, setPercentage] = useState(20)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const load = useCallback(async () => {
    setLoading(true); setError('')
    try {
      const [incomeResponse, goalResponse] = await Promise.all([api.get('/incomes', { params: { mes: month } }), api.get('/savings-goal', { params: { mes: month } })])
      const salary = incomeResponse.data.data.find((item) => item.descricao === 'Salário') || incomeResponse.data.data[0]
      setIncome(salary?.valor ?? '')
      setPercentage(Number(goalResponse.data.data.percentual))
    } catch (requestError) { setError(requestError.userMessage) }
    finally { setLoading(false) }
  }, [month])

  useEffect(() => { load() }, [load])

  const goalValue = useMemo(() => (Number(income) || 0) * percentage / 100, [income, percentage])
  const afterGoal = (Number(income) || 0) - goalValue

  async function submit(event) {
    event.preventDefault(); setSaving(true); setError(''); setNotice('')
    try {
      await Promise.all([
        api.post('/incomes', { descricao: 'Salário', valor: Number(income), mes_referencia: month }),
        api.put('/savings-goal', { percentual: Number(percentage), mes_referencia: month }),
      ])
      setNotice('Renda e meta salvas para este mês.')
    } catch (requestError) { setError(requestError.userMessage) }
    finally { setSaving(false) }
  }

  if (loading) return <LoadingState label="Carregando renda e meta…" />

  return (
    <>
      <PageHeader title="Renda e meta" description={`Defina a base do planejamento de ${monthLabel(month)}.`} />
      {notice && <div className="notice" role="status"><Check size={17} />{notice}</div>}
      {error && <ErrorState message={error} onRetry={load} />}

      <form className="income-layout" onSubmit={submit}>
        <section className="income-form" aria-labelledby="income-title">
          <span className="feature-icon"><Landmark size={24} /></span>
          <h2 id="income-title">Quanto entra neste mês?</h2>
          <p>Use o valor líquido que estará disponível para organizar.</p>
          <label className="currency-input"><span>R$</span><input required min="0" step="0.01" type="number" value={income} onChange={(event) => setIncome(event.target.value)} aria-label="Renda mensal" placeholder="0,00" /></label>
        </section>

        <section className="goal-form" aria-labelledby="goal-title">
          <span className="feature-icon saving"><PiggyBank size={24} /></span>
          <div className="goal-title-row"><div><h2 id="goal-title">Quanto você quer reservar?</h2><p>A sugestão inicial segue a regra 50/30/20.</p></div><strong>{percentage}%</strong></div>
          <input className="range" type="range" min="0" max="100" step="1" value={percentage} onChange={(event) => setPercentage(Number(event.target.value))} aria-label="Percentual da meta de economia" />
          <div className="range-labels"><span>0%</span><span>20% sugerido</span><span>100%</span></div>
          <label className="percentage-input"><span>Percentual exato</span><input type="number" min="0" max="100" step="0.5" value={percentage} onChange={(event) => setPercentage(Number(event.target.value))} /></label>
          <aside className="budget-rule-note" aria-label="Sobre a regra 50 30 20">
            <Info size={18} aria-hidden="true" />
            <div><strong>O que é a regra 50/30/20?</strong><p>É uma referência: 50% para necessidades, 30% para desejos e 20% para poupar ou pagar dívidas. Sua meta pode ser qualquer valor entre 0% e 100%.</p></div>
          </aside>
        </section>

        <section className="goal-result" aria-live="polite">
          <div><span>Sua reserva do mês</span><strong>{money(goalValue)}</strong><small>Esse valor será descontado do saldo disponível.</small></div>
          <div className="result-divider" />
          <div><span>Renda após a reserva</span><strong>{money(afterGoal)}</strong><small>Antes dos gastos fixos e compras.</small></div>
          <button className="button light" disabled={saving}><Save size={18} /> {saving ? 'Salvando…' : 'Salvar planejamento'}</button>
        </section>
      </form>
    </>
  )
}

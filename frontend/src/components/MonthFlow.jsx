import { money } from '../utils/format'

export default function MonthFlow({ summary }) {
  const steps = [
    { label: 'Entrou', value: summary.salario_total, tone: 'income' },
    { label: 'Compromissos', value: summary.total_gastos_fixos + summary.total_compras_eventuais, tone: 'cost' },
    { label: 'Reserva', value: summary.valor_meta_economia, tone: 'saving' },
    { label: 'Livre', value: summary.saldo_disponivel, tone: summary.saldo_disponivel >= 0 ? 'balance' : 'danger' },
  ]
  const salary = Math.max(summary.salario_total, 1)

  return (
    <section className="month-flow" aria-labelledby="flow-title">
      <div className="flow-heading">
        <div><h2 id="flow-title">O caminho do seu dinheiro</h2><p>Da renda ao valor realmente disponível.</p></div>
        <span className={`status ${summary.situacao}`}>{summary.situacao === 'ok' ? 'Mês equilibrado' : 'Orçamento estourado'}</span>
      </div>
      <div className="flow-track">
        {steps.map((step, index) => (
          <div className={`flow-step ${step.tone}`} key={step.label}>
            <span className="flow-index">{index + 1}</span>
            <span className="flow-label">{step.label}</span>
            <strong>{money(step.value)}</strong>
            <span className="flow-bar" style={{ '--portion': `${Math.min(Math.abs(step.value) / salary * 100, 100)}%` }} />
          </div>
        ))}
      </div>
    </section>
  )
}

import { useCallback, useEffect, useState } from 'react'
import { AlertCircle, ArrowRight, CheckCircle2, Clock3 } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { useMonth } from '../context/MonthContext'
import api from '../services/api'
import { money, monthLabel } from '../utils/format'
import { EmptyState, ErrorState, LoadingState } from '../components/Feedback'
import MonthFlow from '../components/MonthFlow'
import PageHeader from '../components/PageHeader'

const chartColors = ['#1f7a62', '#e17855', '#d6a735', '#416f83', '#7e6a9c', '#8a7964']

export default function Dashboard() {
  const { month } = useMonth()
  const [summary, setSummary] = useState(null)
  const [alerts, setAlerts] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const load = useCallback(async () => {
    setLoading(true)
    setError('')
    try {
      const [summaryResponse, alertsResponse] = await Promise.all([
        api.get('/summary', { params: { mes: month } }),
        api.get('/alerts'),
      ])
      setSummary(summaryResponse.data.data)
      setAlerts(alertsResponse.data.data)
    } catch (requestError) {
      setError(requestError.userMessage)
    } finally {
      setLoading(false)
    }
  }, [month])

  useEffect(() => { load() }, [load])

  if (loading) return <LoadingState label={`Organizando ${monthLabel(month)}…`} />
  if (error) return <ErrorState message={error} onRetry={load} />

  const metrics = [
    { label: 'Renda', value: summary.salario_total, detail: 'Total recebido' },
    { label: 'Gastos fixos', value: summary.total_gastos_fixos, detail: `${money(summary.total_pago)} já pagos` },
    { label: 'A pagar', value: summary.total_a_pagar, detail: 'Compromissos pendentes', attention: summary.total_a_pagar > 0 },
    { label: 'Compras', value: summary.total_compras_eventuais, detail: 'Gastos eventuais' },
    { label: 'Meta', value: summary.valor_meta_economia, detail: `${summary.percentual_meta}% da renda` },
    { label: 'Saldo livre', value: summary.saldo_disponivel, detail: 'Após gastos e reserva', strong: true },
  ]

  return (
    <>
      <PageHeader
        title={`Seu mês em ${monthLabel(month)}`}
        description="Veja o que entrou, o que ainda pede atenção e quanto permanece livre."
        action={<Link className="button primary" to="/gastos">Registrar gasto <ArrowRight size={17} /></Link>}
      />

      <MonthFlow summary={summary} />

      <section className="metric-strip" aria-label="Resumo financeiro">
        {metrics.map((metric) => (
          <article key={metric.label} className={`${metric.strong ? 'metric-strong' : ''} ${metric.attention ? 'metric-attention' : ''}`}>
            <span>{metric.label}</span><strong>{money(metric.value)}</strong><small>{metric.detail}</small>
          </article>
        ))}
      </section>

      <div className="dashboard-grid">
        <section className="panel chart-panel" aria-labelledby="category-title">
          <div className="panel-heading"><div><h2 id="category-title">Para onde foi</h2><p>Fixos e compras por categoria.</p></div></div>
          {summary.gastos_por_categoria.length === 0 ? (
            <EmptyState title="Nenhum gasto neste mês" description="Cadastre um gasto ou uma compra para ver a distribuição." />
          ) : (
            <div className="chart-layout">
              <div className="chart-wrap" aria-label="Gráfico de gastos por categoria">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie data={summary.gastos_por_categoria} dataKey="total" nameKey="categoria" innerRadius="54%" outerRadius="83%" paddingAngle={2} stroke="none">
                      {summary.gastos_por_categoria.map((entry, index) => <Cell key={entry.categoria} fill={chartColors[index % chartColors.length]} />)}
                    </Pie>
                    <Tooltip formatter={(value) => money(value)} contentStyle={{ borderRadius: 10, border: 0, boxShadow: '0 8px 24px rgba(18,55,47,.16)' }} />
                  </PieChart>
                </ResponsiveContainer>
                <div className="chart-total"><span>Total</span><strong>{money(summary.total_gastos_fixos + summary.total_compras_eventuais)}</strong></div>
              </div>
              <ul className="chart-legend">
                {summary.gastos_por_categoria.map((item, index) => (
                  <li key={item.categoria}><span style={{ background: chartColors[index % chartColors.length] }} /><span>{item.categoria}</span><strong>{money(item.total)}</strong></li>
                ))}
              </ul>
            </div>
          )}
        </section>

        <section className="panel alerts-panel" aria-labelledby="alerts-title">
          <div className="panel-heading">
            <div><h2 id="alerts-title">Próximos vencimentos</h2><p>Contas dos próximos cinco dias.</p></div>
            {alerts.length > 0 && <span className="count-badge">{alerts.length}</span>}
          </div>
          {alerts.length === 0 ? (
            <div className="all-clear"><CheckCircle2 size={30} /><strong>Nada urgente por aqui</strong><p>Não há contas vencendo nos próximos cinco dias.</p></div>
          ) : (
            <ul className="alert-list">
              {alerts.map((alert) => (
                <li key={alert.id}>
                  <span className="alert-icon"><AlertCircle size={19} /></span>
                  <div><strong>{alert.nome}</strong><small>{alert.dias_restantes === 0 ? 'Vence hoje' : `Vence em ${alert.dias_restantes} dia${alert.dias_restantes > 1 ? 's' : ''}`}</small></div>
                  <span>{money(alert.valor)}</span>
                </li>
              ))}
            </ul>
          )}
          <Link className="text-link" to="/gastos"><Clock3 size={16} /> Ver todos os gastos</Link>
        </section>
      </div>
    </>
  )
}

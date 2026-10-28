import { BarChart3, CalendarDays, CreditCard, HandCoins, Landmark, ReceiptText } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { useMonth } from '../context/MonthContext'
import { monthLabel } from '../utils/format'

const navigation = [
  { to: '/', label: 'Visão geral', icon: BarChart3 },
  { to: '/gastos', label: 'Gastos fixos', icon: ReceiptText },
  { to: '/compras', label: 'Compras', icon: CreditCard },
  { to: '/renda-meta', label: 'Renda e meta', icon: Landmark },
  { to: '/dividas', label: 'Dívidas', icon: HandCoins },
]

export default function Layout({ children }) {
  const { month, setMonth } = useMonth()

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <NavLink className="brand" to="/" aria-label="Compasso, página inicial">
          <span className="brand-mark" aria-hidden="true"><span /></span>
          <span><strong>Compasso</strong><small>Seu mês, em perspectiva</small></span>
        </NavLink>
        <nav className="main-nav" aria-label="Navegação principal">
          {navigation.map(({ to, label, icon: Icon }) => (
            <NavLink key={to} to={to} end={to === '/'}>
              <Icon size={19} strokeWidth={1.9} aria-hidden="true" />
              <span>{label}</span>
            </NavLink>
          ))}
        </nav>
        <div className="sidebar-note">
          <CalendarDays size={18} aria-hidden="true" />
          <span>Planejamento de<br /><strong>{monthLabel(month)}</strong></span>
        </div>
      </aside>

      <div className="workspace">
        <header className="topbar">
          <div>
            <span className="mobile-brand">Compasso</span>
            <p className="month-context">Planejamento mensal</p>
          </div>
          <label className="month-picker">
            <span>Mês de referência</span>
            <input type="month" value={month} onChange={(event) => setMonth(event.target.value)} />
          </label>
        </header>
        <main className="content">{children}</main>
      </div>

      <nav className="mobile-nav" aria-label="Navegação móvel">
        {navigation.map(({ to, label, icon: Icon }) => (
          <NavLink key={to} to={to} end={to === '/'} aria-label={label}>
            <Icon size={20} aria-hidden="true" />
            <span>{label === 'Visão geral' ? 'Resumo' : label.replace(' fixos', '')}</span>
          </NavLink>
        ))}
      </nav>
    </div>
  )
}

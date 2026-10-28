import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import Layout from './components/Layout'
import { LoadingState } from './components/Feedback'

const Dashboard = lazy(() => import('./pages/Dashboard'))
const Expenses = lazy(() => import('./pages/Expenses'))
const Purchases = lazy(() => import('./pages/Purchases'))
const IncomeGoal = lazy(() => import('./pages/IncomeGoal'))
const Debts = lazy(() => import('./pages/Debts'))

export default function App() {
  return (
    <Layout>
      <Suspense fallback={<LoadingState />}>
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/gastos" element={<Expenses />} />
          <Route path="/compras" element={<Purchases />} />
          <Route path="/renda-meta" element={<IncomeGoal />} />
          <Route path="/dividas" element={<Debts />} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </Layout>
  )
}

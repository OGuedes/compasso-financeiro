import { createContext, useContext, useMemo, useState } from 'react'

const MonthContext = createContext(null)

function currentMonth() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

export function MonthProvider({ children }) {
  const [month, setMonth] = useState(currentMonth)
  const value = useMemo(() => ({ month, setMonth }), [month])
  return <MonthContext.Provider value={value}>{children}</MonthContext.Provider>
}

export function useMonth() {
  const context = useContext(MonthContext)
  if (!context) throw new Error('useMonth deve ser usado dentro de MonthProvider')
  return context
}

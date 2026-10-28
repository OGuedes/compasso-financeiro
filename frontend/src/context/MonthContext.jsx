import { createContext, useContext, useEffect, useMemo, useState } from 'react'

const MonthContext = createContext(null)
const MONTH_STORAGE_KEY = 'compasso:selected-month'

function currentMonth() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function initialMonth() {
  const savedMonth = localStorage.getItem(MONTH_STORAGE_KEY)
  return /^\d{4}-(0[1-9]|1[0-2])$/.test(savedMonth ?? '') ? savedMonth : currentMonth()
}

export function MonthProvider({ children }) {
  const [month, setMonth] = useState(initialMonth)

  useEffect(() => {
    localStorage.setItem(MONTH_STORAGE_KEY, month)
  }, [month])

  const value = useMemo(() => ({ month, setMonth }), [month])
  return <MonthContext.Provider value={value}>{children}</MonthContext.Provider>
}

export function useMonth() {
  const context = useContext(MonthContext)
  if (!context) throw new Error('useMonth deve ser usado dentro de MonthProvider')
  return context
}

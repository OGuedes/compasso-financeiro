export const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format

export function monthLabel(month) {
  const [year, monthNumber] = month.split('-').map(Number)
  return new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(new Date(year, monthNumber - 1, 1))
}

export function dateLabel(date) {
  return new Intl.DateTimeFormat('pt-BR').format(new Date(`${date}T12:00:00`))
}

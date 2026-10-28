import { useState } from 'react'
import { Plus } from 'lucide-react'
import api from '../services/api'

export default function CategoryField({ categories, setCategories, value, onChange }) {
  const [adding, setAdding] = useState(false)
  const [name, setName] = useState('')
  const [error, setError] = useState('')

  async function addCategory() {
    if (!name.trim()) return
    try {
      const { data } = await api.post('/categories', { nome: name.trim() })
      setCategories((current) => [...current, data.data].sort((a, b) => a.nome.localeCompare(b.nome)))
      onChange(String(data.data.id))
      setName('')
      setAdding(false)
    } catch (requestError) {
      setError(requestError.userMessage)
    }
  }

  return (
    <div className="category-field">
      <div className="select-with-action">
        <select value={value} onChange={(event) => onChange(event.target.value)}>
          <option value="">Sem categoria</option>
          {categories.map((category) => <option key={category.id} value={category.id}>{category.nome}</option>)}
        </select>
        <button type="button" className="icon-button" onClick={() => setAdding((current) => !current)} aria-label="Adicionar categoria"><Plus size={18} /></button>
      </div>
      {adding && (
        <div className="inline-create">
          <input value={name} onChange={(event) => setName(event.target.value)} placeholder="Nova categoria" aria-label="Nome da nova categoria" />
          <button type="button" className="button secondary compact" onClick={addCategory}>Adicionar</button>
        </div>
      )}
      {error && <small className="field-error">{error}</small>}
    </div>
  )
}

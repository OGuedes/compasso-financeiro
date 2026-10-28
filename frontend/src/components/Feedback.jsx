import { AlertTriangle, LoaderCircle, RefreshCw } from 'lucide-react'

export function LoadingState({ label = 'Carregando seus dados…' }) {
  return <div className="feedback loading" role="status"><LoaderCircle className="spin" size={22} /><span>{label}</span></div>
}

export function ErrorState({ message, onRetry }) {
  return (
    <div className="feedback error" role="alert">
      <AlertTriangle size={22} />
      <div><strong>Algo saiu do previsto</strong><p>{message}</p></div>
      {onRetry && <button className="button secondary compact" onClick={onRetry}><RefreshCw size={16} /> Tentar novamente</button>}
    </div>
  )
}

export function EmptyState({ title, description }) {
  return <div className="empty-state"><span aria-hidden="true" /><strong>{title}</strong><p>{description}</p></div>
}

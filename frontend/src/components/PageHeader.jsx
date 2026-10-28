export default function PageHeader({ title, description, action }) {
  return (
    <header className="page-header">
      <div><h1>{title}</h1><p>{description}</p></div>
      {action}
    </header>
  )
}

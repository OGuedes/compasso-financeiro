import React from 'react'
import ReactDOM from 'react-dom/client'
import '@fontsource-variable/manrope'
import { HashRouter } from 'react-router-dom'
import App from './App'
import { MonthProvider } from './context/MonthContext'
import './styles/global.css'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <HashRouter>
      <MonthProvider>
        <App />
      </MonthProvider>
    </HashRouter>
  </React.StrictMode>,
)

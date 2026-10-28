import axios from 'axios'
import localApi from './localApi'

const developmentApi = 'http://localhost:8000/api'
const packagedApi = `${window.location.origin}/api`

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || (window.location.port === '5173' ? developmentApi : packagedApi),
  timeout: 10000,
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const fields = error.response?.data?.error?.fields
    const fieldMessage = fields ? Object.values(fields)[0] : null
    error.userMessage = fieldMessage || error.response?.data?.error?.message || 'Não foi possível falar com o servidor. Tente novamente.'
    return Promise.reject(error)
  },
)

export default import.meta.env.VITE_DATA_MODE === 'local' ? localApi : api

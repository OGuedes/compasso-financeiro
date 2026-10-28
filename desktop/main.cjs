const { app, BrowserWindow, dialog, shell } = require('electron')
const { spawn } = require('node:child_process')
const fs = require('node:fs')
const http = require('node:http')
const net = require('node:net')
const path = require('node:path')

let backendProcess = null
let backendLog = null
let localOrigin = null

function resourcePath(...parts) {
  const root = app.isPackaged ? process.resourcesPath : path.resolve(__dirname, '..')
  return path.join(root, ...parts)
}

function findPort() {
  return new Promise((resolve, reject) => {
    const server = net.createServer()
    server.unref()
    server.once('error', reject)
    server.listen(0, '127.0.0.1', () => {
      const { port } = server.address()
      server.close(() => resolve(port))
    })
  })
}

function waitForApi(url, attempts = 50) {
  return new Promise((resolve, reject) => {
    const check = (remaining) => {
      const request = http.get(`${url}/api/categories`, (response) => {
        response.resume()
        if (response.statusCode === 200) resolve()
        else if (remaining > 0) setTimeout(() => check(remaining - 1), 100)
        else reject(new Error(`API respondeu com status ${response.statusCode}`))
      })
      request.on('error', () => {
        if (remaining > 0) setTimeout(() => check(remaining - 1), 100)
        else reject(new Error('A API local não iniciou.'))
      })
      request.setTimeout(1000, () => request.destroy())
    }
    check(attempts)
  })
}

async function startBackend() {
  const port = await findPort()
  localOrigin = `http://127.0.0.1:${port}`
  const phpExecutable = app.isPackaged ? resourcePath('php', 'php.exe') : 'php'
  const phpArguments = app.isPackaged
    ? ['-n', '-d', `extension_dir=${resourcePath('php', 'ext')}`, '-d', 'extension=pdo_sqlite', '-d', 'extension=mbstring']
    : []
  const router = app.isPackaged ? resourcePath('router.php') : path.join(__dirname, 'router.php')
  const backend = resourcePath('backend')
  const frontend = app.isPackaged ? resourcePath('frontend') : resourcePath('frontend', 'dist')
  const database = path.join(app.getPath('userData'), 'compasso.sqlite')
  const logPath = path.join(app.getPath('userData'), 'backend.log')

  backendLog = fs.createWriteStream(logPath, { flags: 'a' })
  backendProcess = spawn(phpExecutable, [...phpArguments, '-S', `127.0.0.1:${port}`, router], {
    cwd: app.getPath('userData'),
    windowsHide: true,
    env: {
      ...process.env,
      APP_ENV: 'production',
      APP_DEBUG: 'false',
      FRONTEND_URL: localOrigin,
      DB_DRIVER: 'sqlite',
      DB_DATABASE: database,
      BACKEND_PATH: backend,
      FRONTEND_DIST: frontend,
    },
  })
  backendProcess.stdout.pipe(backendLog)
  backendProcess.stderr.pipe(backendLog)
  backendProcess.once('error', (error) => backendLog.write(`${error.stack}\n`))
  await waitForApi(localOrigin)
}

function createWindow() {
  const window = new BrowserWindow({
    width: 1280,
    height: 820,
    minWidth: 760,
    minHeight: 600,
    show: false,
    backgroundColor: '#f3efe5',
    title: 'Compasso',
    autoHideMenuBar: true,
    webPreferences: {
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true,
    },
  })

  window.webContents.setWindowOpenHandler(({ url }) => {
    if (/^https?:/.test(url) && !url.startsWith(localOrigin)) shell.openExternal(url)
    return { action: 'deny' }
  })
  window.webContents.on('will-navigate', (event, url) => {
    if (!url.startsWith(localOrigin)) event.preventDefault()
  })
  window.once('ready-to-show', () => window.show())
  window.loadURL(localOrigin)
}

function stopBackend() {
  if (backendProcess && !backendProcess.killed) backendProcess.kill()
  if (backendLog) backendLog.end()
  backendProcess = null
  backendLog = null
}

const hasLock = app.requestSingleInstanceLock()
if (!hasLock) app.quit()

app.on('second-instance', () => {
  const window = BrowserWindow.getAllWindows()[0]
  if (window) {
    if (window.isMinimized()) window.restore()
    window.focus()
  }
})

app.whenReady().then(async () => {
  try {
    await startBackend()
    createWindow()
  } catch (error) {
    dialog.showErrorBox('Compasso não iniciou', `${error.message}\n\nConsulte backend.log na pasta de dados do aplicativo.`)
    stopBackend()
    app.quit()
  }
})

app.on('window-all-closed', () => app.quit())
app.on('before-quit', stopBackend)

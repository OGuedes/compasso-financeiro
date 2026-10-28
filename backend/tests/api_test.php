<?php

declare(strict_types=1);

$backend = dirname(__DIR__);
$database = tempnam(sys_get_temp_dir(), 'finance_api_');
$portProbe = stream_socket_server('tcp://127.0.0.1:0', $probeErrorCode, $probeErrorMessage);
if ($portProbe === false) {
    fwrite(STDERR, "FALHA: Não foi possível reservar uma porta de teste.\n");
    exit(1);
}
$probeAddress = stream_socket_get_name($portProbe, false);
$port = (int) substr(strrchr($probeAddress, ':'), 1);
fclose($portProbe);
$baseUrl = "http://127.0.0.1:{$port}";
$log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'finance_api_server.log';
@unlink($log);

function fail(string $message): never
{
    fwrite(STDERR, "FALHA: {$message}\n");
    exit(1);
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) fail($message);
    echo "OK  {$message}\n";
}

function request(string $baseUrl, string $method, string $path, ?array $body = null): array
{
    $curl = curl_init($baseUrl . $path);
    $headers = ['Accept: application/json', 'Origin: http://localhost:5173'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    $response = curl_exec($curl);
    if ($response === false) fail('Erro HTTP: ' . curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $headerText = substr($response, 0, $headerSize);
    $rawBody = substr($response, $headerSize);
    return [
        'status' => $status,
        'headers' => $headerText,
        'body' => $rawBody === '' ? null : json_decode($rawBody, true),
    ];
}

$pdo = new PDO("sqlite:{$database}");
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(<<<'SQL'
CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL UNIQUE);
CREATE TABLE incomes (id INTEGER PRIMARY KEY AUTOINCREMENT, descricao TEXT NOT NULL, valor NUMERIC NOT NULL, mes_referencia TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(descricao, mes_referencia));
CREATE TABLE expenses (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, valor NUMERIC NOT NULL, pago INTEGER NOT NULL DEFAULT 0, dia_vencimento INTEGER, categoria_id INTEGER, mes_referencia TEXT NOT NULL, recorrente INTEGER NOT NULL DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(categoria_id) REFERENCES categories(id) ON DELETE SET NULL);
CREATE TABLE purchases (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, valor NUMERIC NOT NULL, data TEXT NOT NULL, categoria_id INTEGER, observacao TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(categoria_id) REFERENCES categories(id) ON DELETE SET NULL);
CREATE TABLE savings_goals (id INTEGER PRIMARY KEY AUTOINCREMENT, mes_referencia TEXT NOT NULL UNIQUE, percentual NUMERIC NOT NULL DEFAULT 20);
SQL);

$environment = array_merge(getenv(), [
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'true',
    'FRONTEND_URL' => 'http://localhost:5173',
    'DB_DRIVER' => 'sqlite',
    'DB_DATABASE' => $database,
]);
$command = [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $backend . '/public', $backend . '/public/index.php'];
$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['file', $log, 'a'],
    2 => ['file', $log, 'a'],
];
$process = proc_open($command, $descriptors, $pipes, $backend, $environment);
if (!is_resource($process)) fail('Não foi possível iniciar o servidor de teste.');
fclose($pipes[0]);

try {
    $ready = false;
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $socket = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.1);
        if ($socket) {
            fclose($socket);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    if (!$ready) fail('Servidor de teste não iniciou. Log: ' . (@file_get_contents($log) ?: 'vazio'));

    $category = request($baseUrl, 'POST', '/api/categories', ['nome' => 'Moradia']);
    assertTrue($category['status'] === 201, 'cria categoria');
    $categoryId = (int) $category['body']['data']['id'];
    assertTrue(str_contains($category['headers'], 'Access-Control-Allow-Origin: http://localhost:5173'), 'envia cabeçalho CORS permitido');

    $income = request($baseUrl, 'POST', '/api/incomes', ['valor' => 5000, 'mes_referencia' => '2026-10']);
    assertTrue($income['status'] === 201 && (float) $income['body']['data']['valor'] === 5000.0, 'cria renda mensal');
    $incomeUpdate = request($baseUrl, 'POST', '/api/incomes', ['valor' => 5400, 'mes_referencia' => '2026-10']);
    assertTrue((float) $incomeUpdate['body']['data']['valor'] === 5400.0, 'atualiza renda do mesmo mês');
    $incomeList = request($baseUrl, 'GET', '/api/incomes?mes=2026-10');
    assertTrue($incomeList['status'] === 200 && count($incomeList['body']['data']) === 1, 'lista rendas do mês');

    $expense = request($baseUrl, 'POST', '/api/expenses', [
        'nome' => 'Aluguel', 'valor' => 1600, 'mes_referencia' => '2026-10',
        'dia_vencimento' => 5, 'categoria_id' => $categoryId, 'recorrente' => true,
    ]);
    assertTrue($expense['status'] === 201, 'cria gasto fixo');
    $expenseId = (int) $expense['body']['data']['id'];

    $edited = request($baseUrl, 'PUT', "/api/expenses/{$expenseId}", ['valor' => 1650]);
    assertTrue($edited['status'] === 200 && (float) $edited['body']['data']['valor'] === 1650.0, 'edita gasto preservando os demais campos');
    $paid = request($baseUrl, 'PATCH', "/api/expenses/{$expenseId}/pay");
    assertTrue($paid['status'] === 200 && (int) $paid['body']['data']['pago'] === 1, 'alterna status de pagamento');
    $expenseList = request($baseUrl, 'GET', '/api/expenses?mes=2026-10&sort=valor&order=asc');
    assertTrue($expenseList['status'] === 200 && count($expenseList['body']['data']) === 1, 'lista e ordena gastos fixos');

    $purchase = request($baseUrl, 'POST', '/api/purchases', [
        'nome' => 'Mercado', 'valor' => 420, 'data' => '2026-10-10', 'categoria_id' => $categoryId,
    ]);
    assertTrue($purchase['status'] === 201, 'cria compra eventual');
    $purchaseId = (int) $purchase['body']['data']['id'];
    $purchaseList = request($baseUrl, 'GET', '/api/purchases?mes=2026-10');
    assertTrue($purchaseList['status'] === 200 && count($purchaseList['body']['data']) === 1, 'lista compras eventuais do mês');

    $goal = request($baseUrl, 'PUT', '/api/savings-goal', ['mes_referencia' => '2026-10', 'percentual' => 25]);
    assertTrue($goal['status'] === 200 && (float) $goal['body']['data']['percentual'] === 25.0, 'define meta de economia');
    $goalRead = request($baseUrl, 'GET', '/api/savings-goal?mes=2026-10');
    assertTrue($goalRead['status'] === 200 && (float) $goalRead['body']['data']['percentual'] === 25.0, 'consulta meta de economia');

    $summary = request($baseUrl, 'GET', '/api/summary?mes=2026-10');
    $summaryData = $summary['body']['data'];
    assertTrue($summary['status'] === 200 && (float) $summaryData['saldo_disponivel'] === 1980.0, 'calcula resumo e saldo mensal');
    assertTrue($summaryData['situacao'] === 'ok' && count($summaryData['gastos_por_categoria']) === 1, 'calcula situação e agrupamento por categoria');

    $debt = request($baseUrl, 'POST', '/api/debts', [
        'nome' => 'Empréstimo pessoal', 'credor' => 'Banco Exemplo', 'valor_total' => 1200,
        'total_parcelas' => 12, 'parcelas_pagas' => 2, 'valor_pago' => 200, 'dia_vencimento' => 15,
    ]);
    assertTrue($debt['status'] === 201 && (float) $debt['body']['data']['saldo_devedor'] === 1000.0, 'cria dívida e calcula saldo devedor');
    $debtId = (int) $debt['body']['data']['id'];
    $paidInstallment = request($baseUrl, 'PATCH', "/api/debts/{$debtId}/pay-installment");
    assertTrue($paidInstallment['status'] === 200 && (int) $paidInstallment['body']['data']['parcelas_pagas'] === 3, 'registra pagamento de parcela');
    $debtList = request($baseUrl, 'GET', '/api/debts?status=open');
    assertTrue($debtList['status'] === 200 && count($debtList['body']['data']) === 1, 'lista dívidas em aberto');
    $debtUpdate = request($baseUrl, 'PUT', "/api/debts/{$debtId}", ['observacao' => 'Acordo renegociado']);
    assertTrue($debtUpdate['status'] === 200 && $debtUpdate['body']['data']['observacao'] === 'Acordo renegociado', 'edita dívida preservando os demais campos');

    $tomorrow = new DateTimeImmutable('tomorrow');
    request($baseUrl, 'POST', '/api/expenses', [
        'nome' => 'Conta próxima', 'valor' => 1, 'mes_referencia' => $tomorrow->format('Y-m'),
        'dia_vencimento' => (int) $tomorrow->format('d'), 'categoria_id' => $categoryId,
    ]);
    $alerts = request($baseUrl, 'GET', '/api/alerts');
    assertTrue($alerts['status'] === 200 && count($alerts['body']['data']) >= 1, 'lista contas a vencer nos próximos cinco dias');

    $copy = request($baseUrl, 'POST', '/api/expenses/copy', ['de' => '2026-10', 'para' => '2026-11']);
    assertTrue($copy['status'] === 201 && $copy['body']['data']['copiados'] === 1, 'copia gastos recorrentes');
    $copyAgain = request($baseUrl, 'POST', '/api/expenses/copy', ['de' => '2026-10', 'para' => '2026-11']);
    assertTrue($copyAgain['body']['data']['copiados'] === 0, 'evita duplicar gasto recorrente no destino');

    $invalid = request($baseUrl, 'POST', '/api/expenses', ['nome' => '', 'valor' => -1, 'mes_referencia' => '10/2026']);
    assertTrue($invalid['status'] === 422 && isset($invalid['body']['error']['fields']['nome']), 'retorna erros claros de validação');

    $missing = request($baseUrl, 'GET', '/api/rota-inexistente');
    assertTrue($missing['status'] === 404, 'retorna 404 para rota inexistente');

    $categoryList = request($baseUrl, 'GET', '/api/categories');
    assertTrue($categoryList['status'] === 200 && count($categoryList['body']['data']) === 1, 'lista categorias');

    $deletePurchase = request($baseUrl, 'DELETE', "/api/purchases/{$purchaseId}");
    $deleteExpense = request($baseUrl, 'DELETE', "/api/expenses/{$expenseId}");
    $deleteDebt = request($baseUrl, 'DELETE', "/api/debts/{$debtId}");
    assertTrue($deletePurchase['status'] === 204 && $deleteExpense['status'] === 204 && $deleteDebt['status'] === 204, 'remove compra, gasto fixo e dívida');

    echo "\nTodos os testes de API passaram.\n";
} finally {
    proc_terminate($process);
    proc_close($process);
    @unlink($database);
}

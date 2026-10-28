<?php

declare(strict_types=1);

use App\Config\Env;
use App\Core\HttpException;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Middleware\CorsMiddleware;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

Env::load(BASE_PATH . '/.env');
CorsMiddleware::handle();

try {
    $router = require BASE_PATH . '/routes/api.php';
    $router->dispatch(Request::capture());
} catch (HttpException $exception) {
    JsonResponse::error($exception->getMessage(), $exception->status, $exception->errors);
} catch (Throwable $exception) {
    error_log((string) $exception);
    $message = Env::bool('APP_DEBUG') ? $exception->getMessage() : 'Erro interno do servidor.';
    JsonResponse::error($message, 500);
}

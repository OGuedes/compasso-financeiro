<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Env;

final class CorsMiddleware
{
    public static function handle(): void
    {
        $allowedOrigin = Env::get('FRONTEND_URL', 'http://localhost:5173');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === $allowedOrigin) {
            header("Access-Control-Allow-Origin: {$allowedOrigin}");
            header('Vary: Origin');
        }
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Max-Age: 86400');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

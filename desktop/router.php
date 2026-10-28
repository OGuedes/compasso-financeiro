<?php

declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$backendPath = getenv('BACKEND_PATH') ?: dirname(__DIR__) . '/backend';
$frontendPath = getenv('FRONTEND_DIST') ?: dirname(__DIR__) . '/frontend/dist';

if ($path === '/api' || str_starts_with($path, '/api/')) {
    require $backendPath . '/public/index.php';
    return true;
}

$relativePath = ltrim($path, '/');
$candidate = realpath($frontendPath . DIRECTORY_SEPARATOR . $relativePath);
$frontendRoot = realpath($frontendPath);

if ($relativePath !== '' && $candidate !== false && $frontendRoot !== false
    && str_starts_with($candidate, $frontendRoot . DIRECTORY_SEPARATOR) && is_file($candidate)) {
    $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'woff2' => 'font/woff2',
        'ico' => 'image/x-icon',
    ];
    header('Content-Type: ' . ($types[$extension] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($candidate);
    return true;
}

$index = $frontendPath . DIRECTORY_SEPARATOR . 'index.html';
if (!is_file($index)) {
    http_response_code(503);
    echo 'Interface do Compasso não encontrada.';
    return true;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
readfile($index);
return true;

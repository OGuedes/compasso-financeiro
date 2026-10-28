<?php

declare(strict_types=1);

namespace App\Core;

final class JsonResponse
{
    public static function send(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        exit;
    }

    public static function success(mixed $data, int $status = 200): never
    {
        self::send(['data' => $data], $status);
    }

    public static function error(string $message, int $status, array $errors = []): never
    {
        $payload = ['error' => ['message' => $message]];
        if ($errors !== []) {
            $payload['error']['fields'] = $errors;
        }
        self::send($payload, $status);
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private ?array $body = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly string $rawBody,
    ) {
    }

    public static function capture(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/'),
            $_GET,
            file_get_contents('php://input') ?: '',
        );
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function json(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }
        if ($this->rawBody === '') {
            return $this->body = [];
        }

        $decoded = json_decode($this->rawBody, true);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(400, 'O corpo da requisição deve conter um JSON válido.');
        }
        return $this->body = $decoded;
    }
}

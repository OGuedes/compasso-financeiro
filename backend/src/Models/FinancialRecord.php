<?php

declare(strict_types=1);

namespace App\Models;

abstract readonly class FinancialRecord
{
    public function __construct(
        public ?int $id,
        public string $nome,
        public float $valor,
    ) {}
}

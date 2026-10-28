<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Purchase extends FinancialRecord
{
    public function __construct(?int $id, string $nome, float $valor, public string $data)
    {
        parent::__construct($id, $nome, $valor);
    }
}

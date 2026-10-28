<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Income extends FinancialRecord
{
    public function __construct(?int $id, string $descricao, float $valor, public string $mesReferencia)
    {
        parent::__construct($id, $descricao, $valor);
    }
}

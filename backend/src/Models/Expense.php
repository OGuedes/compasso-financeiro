<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Expense extends FinancialRecord
{
    public function __construct(
        ?int $id,
        string $nome,
        float $valor,
        public bool $pago,
        public string $mesReferencia,
    ) {
        parent::__construct($id, $nome, $valor);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Debt extends FinancialRecord
{
    public function __construct(
        ?int $id,
        string $nome,
        float $valorTotal,
        public int $totalParcelas,
        public int $parcelasPagas,
        public float $valorPago,
    ) {
        parent::__construct($id, $nome, $valorTotal);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use App\Repositories\SummaryRepository;

final class SummaryService
{
    public function __construct(private readonly SummaryRepository $repository) {}

    public function get(string $month): array
    {
        $month = Validator::requireMonth($month);
        $raw = $this->repository->forMonth($month);
        $salary = (float) $raw['incomes']['total'];
        $fixed = (float) $raw['expenses']['total'];
        $paid = (float) $raw['expenses']['pago'];
        $payable = (float) $raw['expenses']['a_pagar'];
        $purchases = (float) $raw['purchases']['total'];
        $percentage = isset($raw['goal']['percentual']) ? (float) $raw['goal']['percentual'] : 20.0;
        $goal = round($salary * $percentage / 100, 2);
        $balance = round($salary - $fixed - $purchases - $goal, 2);
        return [
            'mes_referencia' => $month,
            'salario_total' => $salary,
            'total_gastos_fixos' => $fixed,
            'total_pago' => $paid,
            'total_a_pagar' => $payable,
            'total_compras_eventuais' => $purchases,
            'percentual_meta' => $percentage,
            'valor_meta_economia' => $goal,
            'saldo_disponivel' => $balance,
            'gastos_por_categoria' => array_map(static fn(array $row) => ['categoria' => $row['categoria'], 'total' => (float) $row['total']], $raw['categories']),
            'situacao' => $balance >= 0 ? 'ok' : 'estourado',
        ];
    }
}

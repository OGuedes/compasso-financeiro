<?php

declare(strict_types=1);

namespace App\Repositories;

final class SummaryRepository extends BaseRepository
{
    public function forMonth(string $month): array
    {
        $incomes = $this->fetch('SELECT COALESCE(SUM(valor), 0) total FROM incomes WHERE mes_referencia = :mes', ['mes' => $month]);
        $expenses = $this->fetch(
            'SELECT COALESCE(SUM(valor), 0) total,
                    COALESCE(SUM(CASE WHEN pago = 1 THEN valor ELSE 0 END), 0) pago,
                    COALESCE(SUM(CASE WHEN pago = 0 THEN valor ELSE 0 END), 0) a_pagar
             FROM expenses WHERE mes_referencia = :mes',
            ['mes' => $month]
        );
        $start = "{$month}-01";
        $end = date('Y-m-d', strtotime("{$start} +1 month"));
        $purchases = $this->fetch(
            'SELECT COALESCE(SUM(valor), 0) total FROM purchases WHERE data >= :inicio AND data < :fim',
            ['inicio' => $start, 'fim' => $end]
        );
        $goal = $this->fetch('SELECT percentual FROM savings_goals WHERE mes_referencia = :mes', ['mes' => $month]);
        $categories = $this->fetchAll(
            'SELECT categoria, SUM(total) total FROM (
                SELECT COALESCE(c.nome, \'Sem categoria\') categoria, e.valor total
                FROM expenses e LEFT JOIN categories c ON c.id = e.categoria_id WHERE e.mes_referencia = :mes_exp
                UNION ALL
                SELECT COALESCE(c.nome, \'Sem categoria\') categoria, p.valor total
                FROM purchases p LEFT JOIN categories c ON c.id = p.categoria_id
                WHERE p.data >= :inicio AND p.data < :fim
            ) itens GROUP BY categoria ORDER BY total DESC',
            ['mes_exp' => $month, 'inicio' => $start, 'fim' => $end]
        );

        return compact('incomes', 'expenses', 'purchases', 'goal', 'categories');
    }
}

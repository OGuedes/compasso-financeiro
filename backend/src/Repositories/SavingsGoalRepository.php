<?php

declare(strict_types=1);

namespace App\Repositories;

final class SavingsGoalRepository extends BaseRepository
{
    public function findByMonth(string $month): ?array
    {
        return $this->fetch(
            'SELECT id, mes_referencia, percentual FROM savings_goals WHERE mes_referencia = :mes',
            ['mes' => $month]
        );
    }

    public function upsert(string $month, float $percentage, bool $sqlite = false): array
    {
        $sql = $sqlite
            ? 'INSERT INTO savings_goals (mes_referencia, percentual) VALUES (:mes, :percentual)
               ON CONFLICT(mes_referencia) DO UPDATE SET percentual = excluded.percentual'
            : 'INSERT INTO savings_goals (mes_referencia, percentual) VALUES (:mes, :percentual)
               ON DUPLICATE KEY UPDATE percentual = VALUES(percentual), id = LAST_INSERT_ID(id)';
        $this->execute($sql, ['mes' => $month, 'percentual' => $percentage]);
        return $this->findByMonth($month) ?? [];
    }
}

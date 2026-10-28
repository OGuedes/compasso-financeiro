<?php

declare(strict_types=1);

namespace App\Repositories;

final class IncomeRepository extends BaseRepository
{
    public function upsert(array $data, bool $sqlite = false): array
    {
        $sql = $sqlite
            ? 'INSERT INTO incomes (descricao, valor, mes_referencia) VALUES (:descricao, :valor, :mes)
               ON CONFLICT(descricao, mes_referencia) DO UPDATE SET valor = excluded.valor'
            : 'INSERT INTO incomes (descricao, valor, mes_referencia) VALUES (:descricao, :valor, :mes)
               ON DUPLICATE KEY UPDATE valor = VALUES(valor), id = LAST_INSERT_ID(id)';
        $this->execute($sql, ['descricao' => $data['descricao'], 'valor' => $data['valor'], 'mes' => $data['mes_referencia']]);
        return $this->findByDescriptionAndMonth($data['descricao'], $data['mes_referencia']);
    }

    public function findByDescriptionAndMonth(string $description, string $month): array
    {
        return $this->fetch(
            'SELECT id, descricao, valor, mes_referencia, created_at FROM incomes WHERE descricao = :descricao AND mes_referencia = :mes',
            ['descricao' => $description, 'mes' => $month]
        ) ?? [];
    }

    public function byMonth(string $month): array
    {
        return $this->fetchAll(
            'SELECT id, descricao, valor, mes_referencia, created_at FROM incomes WHERE mes_referencia = :mes ORDER BY id',
            ['mes' => $month]
        );
    }
}

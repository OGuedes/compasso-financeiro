<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;

final class ExpenseRepository extends BaseRepository
{
    private const SELECT = 'SELECT e.id, e.nome, e.valor, e.pago, e.dia_vencimento, e.categoria_id,
        c.nome AS categoria_nome, e.mes_referencia, e.recorrente, e.created_at
        FROM expenses e LEFT JOIN categories c ON c.id = e.categoria_id';

    public function create(array $data): array
    {
        $this->execute(
            'INSERT INTO expenses (nome, valor, pago, dia_vencimento, categoria_id, mes_referencia, recorrente)
             VALUES (:nome, :valor, :pago, :dia, :categoria, :mes, :recorrente)',
            $this->params($data)
        );
        return $this->find((int) $this->pdo->lastInsertId()) ?? [];
    }

    public function find(int $id): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE e.id = :id', ['id' => $id]);
    }

    public function byMonth(string $month, string $sort, string $order): array
    {
        $column = $sort === 'nome' ? 'e.nome' : 'e.valor';
        $direction = $order === 'desc' ? 'DESC' : 'ASC';
        return $this->fetchAll(
            self::SELECT . " WHERE e.mes_referencia = :mes ORDER BY {$column} {$direction}, e.id ASC",
            ['mes' => $month]
        );
    }

    public function update(int $id, array $data): array
    {
        $params = $this->params($data) + ['id' => $id];
        $this->execute(
            'UPDATE expenses SET nome = :nome, valor = :valor, pago = :pago, dia_vencimento = :dia,
             categoria_id = :categoria, mes_referencia = :mes, recorrente = :recorrente WHERE id = :id',
            $params
        );
        return $this->find($id) ?? [];
    }

    public function togglePaid(int $id): ?array
    {
        if ($this->execute('UPDATE expenses SET pago = CASE WHEN pago = 1 THEN 0 ELSE 1 END WHERE id = :id', ['id' => $id]) === 0) {
            return null;
        }
        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM expenses WHERE id = :id', ['id' => $id]) > 0;
    }

    public function copyRecurring(string $from, string $to): int
    {
        return $this->execute(
            'INSERT INTO expenses (nome, valor, pago, dia_vencimento, categoria_id, mes_referencia, recorrente)
             SELECT e.nome, e.valor, 0, e.dia_vencimento, e.categoria_id, :para, e.recorrente
             FROM expenses e
             WHERE e.mes_referencia = :de AND e.recorrente = 1
               AND NOT EXISTS (
                   SELECT 1 FROM expenses target
                   WHERE target.mes_referencia = :para_check AND target.nome = e.nome
               )',
            ['para' => $to, 'de' => $from, 'para_check' => $to]
        );
    }

    public function alerts(bool $sqlite = false): array
    {
        if ($sqlite) {
            return $this->fetchAll(
                self::SELECT . " WHERE e.pago = 0 AND e.dia_vencimento IS NOT NULL
                AND date(e.mes_referencia || '-' || printf('%02d', e.dia_vencimento))
                BETWEEN date('now') AND date('now', '+5 days')
                ORDER BY e.mes_referencia, e.dia_vencimento"
            );
        }

        return $this->fetchAll(
            self::SELECT . " WHERE e.pago = 0 AND e.dia_vencimento IS NOT NULL
            AND STR_TO_DATE(CONCAT(e.mes_referencia, '-', LPAD(LEAST(e.dia_vencimento,
                DAY(LAST_DAY(CONCAT(e.mes_referencia, '-01')))), 2, '0')), '%Y-%m-%d')
                BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
            ORDER BY e.mes_referencia, e.dia_vencimento"
        );
    }

    private function params(array $data): array
    {
        return [
            'nome' => $data['nome'],
            'valor' => $data['valor'],
            'pago' => (int) $data['pago'],
            'dia' => $data['dia_vencimento'],
            'categoria' => $data['categoria_id'],
            'mes' => $data['mes_referencia'],
            'recorrente' => (int) $data['recorrente'],
        ];
    }
}

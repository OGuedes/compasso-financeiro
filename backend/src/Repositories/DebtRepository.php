<?php

declare(strict_types=1);

namespace App\Repositories;

final class DebtRepository extends BaseRepository
{
    private const SELECT = 'SELECT id, nome, credor, valor_total, total_parcelas,
        parcelas_pagas, valor_pago, dia_vencimento, observacao, created_at FROM debts';

    public function all(string $status = 'all'): array
    {
        $where = match ($status) {
            'open' => ' WHERE valor_pago < valor_total AND parcelas_pagas < total_parcelas',
            'paid' => ' WHERE valor_pago >= valor_total OR parcelas_pagas >= total_parcelas',
            default => '',
        };
        return $this->fetchAll(self::SELECT . $where .
            ' ORDER BY CASE WHEN valor_pago >= valor_total OR parcelas_pagas >= total_parcelas THEN 1 ELSE 0 END, dia_vencimento, id DESC');
    }

    public function find(int $id): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE id = :id', ['id' => $id]);
    }

    public function create(array $data): array
    {
        $this->execute(
            'INSERT INTO debts (nome, credor, valor_total, total_parcelas, parcelas_pagas, valor_pago, dia_vencimento, observacao)
             VALUES (:nome, :credor, :valor_total, :total_parcelas, :parcelas_pagas, :valor_pago, :dia_vencimento, :observacao)',
            $data
        );
        return $this->find((int) $this->pdo->lastInsertId()) ?? [];
    }

    public function update(int $id, array $data): array
    {
        $this->execute(
            'UPDATE debts SET nome = :nome, credor = :credor, valor_total = :valor_total,
             total_parcelas = :total_parcelas, parcelas_pagas = :parcelas_pagas,
             valor_pago = :valor_pago, dia_vencimento = :dia_vencimento, observacao = :observacao
             WHERE id = :id',
            $data + ['id' => $id]
        );
        return $this->find($id) ?? [];
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM debts WHERE id = :id', ['id' => $id]) > 0;
    }
}

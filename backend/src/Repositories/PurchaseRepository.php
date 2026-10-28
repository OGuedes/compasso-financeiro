<?php

declare(strict_types=1);

namespace App\Repositories;

final class PurchaseRepository extends BaseRepository
{
    private const SELECT = 'SELECT p.id, p.nome, p.valor, p.data, p.categoria_id,
        c.nome AS categoria_nome, p.observacao, p.created_at
        FROM purchases p LEFT JOIN categories c ON c.id = p.categoria_id';

    public function create(array $data): array
    {
        $this->execute(
            'INSERT INTO purchases (nome, valor, data, categoria_id, observacao)
             VALUES (:nome, :valor, :data, :categoria, :observacao)',
            $this->params($data)
        );
        return $this->find((int) $this->pdo->lastInsertId()) ?? [];
    }

    public function find(int $id): ?array
    {
        return $this->fetch(self::SELECT . ' WHERE p.id = :id', ['id' => $id]);
    }

    public function byMonth(string $month): array
    {
        return $this->fetchAll(
            self::SELECT . " WHERE p.data >= :inicio AND p.data < :fim ORDER BY p.data DESC, p.id DESC",
            ['inicio' => "{$month}-01", 'fim' => date('Y-m-d', strtotime("{$month}-01 +1 month"))]
        );
    }

    public function update(int $id, array $data): array
    {
        $this->execute(
            'UPDATE purchases SET nome = :nome, valor = :valor, data = :data,
             categoria_id = :categoria, observacao = :observacao WHERE id = :id',
            $this->params($data) + ['id' => $id]
        );
        return $this->find($id) ?? [];
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM purchases WHERE id = :id', ['id' => $id]) > 0;
    }

    private function params(array $data): array
    {
        return [
            'nome' => $data['nome'],
            'valor' => $data['valor'],
            'data' => $data['data'],
            'categoria' => $data['categoria_id'],
            'observacao' => $data['observacao'],
        ];
    }
}

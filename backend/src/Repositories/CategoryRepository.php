<?php

declare(strict_types=1);

namespace App\Repositories;

final class CategoryRepository extends BaseRepository
{
    public function all(): array
    {
        return $this->fetchAll('SELECT id, nome FROM categories ORDER BY nome');
    }

    public function create(string $name): array
    {
        $this->execute('INSERT INTO categories (nome) VALUES (:nome)', ['nome' => $name]);
        return $this->find((int) $this->pdo->lastInsertId()) ?? [];
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT id, nome FROM categories WHERE id = :id', ['id' => $id]);
    }

    public function existsByName(string $name): bool
    {
        return $this->fetch('SELECT id FROM categories WHERE LOWER(nome) = LOWER(:nome)', ['nome' => $name]) !== null;
    }
}

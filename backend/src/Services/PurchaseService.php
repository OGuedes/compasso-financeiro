<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\Validator;
use App\Repositories\CategoryRepository;
use App\Repositories\PurchaseRepository;

final class PurchaseService
{
    public function __construct(
        private readonly PurchaseRepository $repository,
        private readonly CategoryRepository $categories,
    ) {}

    public function create(array $input): array { return $this->repository->create($this->validate($input)); }
    public function list(string $month): array { return $this->repository->byMonth(Validator::requireMonth($month)); }

    public function update(int $id, array $input): array
    {
        $current = $this->repository->find($id);
        if ($current === null) throw new HttpException(404, 'Compra não encontrada.');
        return $this->repository->update($id, $this->validate(array_merge($current, $input)));
    }

    public function delete(int $id): void
    {
        if (!$this->repository->delete($id)) throw new HttpException(404, 'Compra não encontrada.');
    }

    private function validate(array $input): array
    {
        $name = trim((string) ($input['nome'] ?? ''));
        $categoryId = ($input['categoria_id'] ?? null) === '' ? null : ($input['categoria_id'] ?? null);
        $note = trim((string) ($input['observacao'] ?? ''));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) $errors['nome'] = 'Informe um nome com até 150 caracteres.';
        if (!Validator::positiveNumber($input['valor'] ?? null)) $errors['valor'] = 'Informe um valor maior que zero.';
        if (!Validator::date($input['data'] ?? null)) $errors['data'] = 'Use uma data válida no formato YYYY-MM-DD.';
        if ($categoryId !== null && (filter_var($categoryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false || $this->categories->find((int) $categoryId) === null)) $errors['categoria_id'] = 'Categoria não encontrada.';
        Validator::throwIf($errors);
        return [
            'nome' => $name,
            'valor' => round((float) $input['valor'], 2),
            'data' => $input['data'],
            'categoria_id' => $categoryId === null ? null : (int) $categoryId,
            'observacao' => $note === '' ? null : $note,
        ];
    }
}

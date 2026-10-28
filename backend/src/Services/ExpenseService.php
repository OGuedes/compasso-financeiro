<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Core\HttpException;
use App\Core\Validator;
use App\Repositories\CategoryRepository;
use App\Repositories\ExpenseRepository;

final class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepository $repository,
        private readonly CategoryRepository $categories,
    ) {}

    public function create(array $input): array
    {
        return $this->repository->create($this->validate($input));
    }

    public function list(string $month, string $sort, string $order): array
    {
        $month = Validator::requireMonth($month);
        if (!in_array($sort, ['valor', 'nome'], true)) throw new HttpException(422, 'Ordenação inválida.', ['sort' => 'Use valor ou nome.']);
        if (!in_array($order, ['asc', 'desc'], true)) throw new HttpException(422, 'Ordenação inválida.', ['order' => 'Use asc ou desc.']);
        return $this->repository->byMonth($month, $sort, $order);
    }

    public function update(int $id, array $input): array
    {
        $current = $this->repository->find($id);
        if ($current === null) throw new HttpException(404, 'Gasto fixo não encontrado.');
        return $this->repository->update($id, $this->validate(array_merge($current, $input)));
    }

    public function togglePaid(int $id): array
    {
        $expense = $this->repository->togglePaid($id);
        if ($expense === null) throw new HttpException(404, 'Gasto fixo não encontrado.');
        return $expense;
    }

    public function delete(int $id): void
    {
        if (!$this->repository->delete($id)) throw new HttpException(404, 'Gasto fixo não encontrado.');
    }

    public function copy(array $input): array
    {
        $from = $input['de'] ?? null;
        $to = $input['para'] ?? null;
        $errors = [];
        if (!Validator::month($from)) $errors['de'] = 'Use o formato YYYY-MM.';
        if (!Validator::month($to)) $errors['para'] = 'Use o formato YYYY-MM.';
        if ($from === $to) $errors['para'] = 'O mês de destino deve ser diferente da origem.';
        Validator::throwIf($errors);
        return ['copiados' => $this->repository->copyRecurring($from, $to), 'de' => $from, 'para' => $to];
    }

    public function alerts(): array
    {
        $rows = $this->repository->alerts(Database::driver() === 'sqlite');
        return array_map(static function (array $row): array {
            $dueDate = sprintf('%s-%02d', $row['mes_referencia'], $row['dia_vencimento']);
            $row['data_vencimento'] = $dueDate;
            $row['dias_restantes'] = (int) (new \DateTimeImmutable('today'))->diff(new \DateTimeImmutable($dueDate))->format('%r%a');
            return $row;
        }, $rows);
    }

    private function validate(array $input): array
    {
        $name = trim((string) ($input['nome'] ?? ''));
        $categoryId = ($input['categoria_id'] ?? null) === '' ? null : ($input['categoria_id'] ?? null);
        $dueDay = ($input['dia_vencimento'] ?? null) === '' ? null : ($input['dia_vencimento'] ?? null);
        $errors = [];
        if ($name === '' || mb_strlen($name) > 150) $errors['nome'] = 'Informe um nome com até 150 caracteres.';
        if (!Validator::positiveNumber($input['valor'] ?? null)) $errors['valor'] = 'Informe um valor maior que zero.';
        if (!Validator::month($input['mes_referencia'] ?? null)) $errors['mes_referencia'] = 'Use o formato YYYY-MM.';
        if ($dueDay !== null && filter_var($dueDay, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 31]]) === false) $errors['dia_vencimento'] = 'Informe um dia entre 1 e 31.';
        if ($categoryId !== null && (filter_var($categoryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false || $this->categories->find((int) $categoryId) === null)) $errors['categoria_id'] = 'Categoria não encontrada.';
        Validator::throwIf($errors);

        return [
            'nome' => $name,
            'valor' => round((float) $input['valor'], 2),
            'pago' => filter_var($input['pago'] ?? false, FILTER_VALIDATE_BOOL),
            'dia_vencimento' => $dueDay === null ? null : (int) $dueDay,
            'categoria_id' => $categoryId === null ? null : (int) $categoryId,
            'mes_referencia' => $input['mes_referencia'],
            'recorrente' => filter_var($input['recorrente'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Core\Validator;
use App\Repositories\IncomeRepository;

final class IncomeService
{
    public function __construct(private readonly IncomeRepository $repository) {}

    public function save(array $input): array
    {
        $description = trim((string) ($input['descricao'] ?? 'Salário'));
        $errors = [];
        if ($description === '' || mb_strlen($description) > 150) $errors['descricao'] = 'Informe uma descrição com até 150 caracteres.';
        if (!Validator::positiveNumber($input['valor'] ?? null, true)) $errors['valor'] = 'Informe um valor maior ou igual a zero.';
        if (!Validator::month($input['mes_referencia'] ?? null)) $errors['mes_referencia'] = 'Use o formato YYYY-MM.';
        Validator::throwIf($errors);

        return $this->repository->upsert([
            'descricao' => $description,
            'valor' => round((float) $input['valor'], 2),
            'mes_referencia' => $input['mes_referencia'],
        ], Database::driver() === 'sqlite');
    }

    public function list(string $month): array
    {
        return $this->repository->byMonth(Validator::requireMonth($month));
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Core\Validator;
use App\Repositories\SavingsGoalRepository;

final class SavingsGoalService
{
    public function __construct(private readonly SavingsGoalRepository $repository) {}

    public function get(string $month): array
    {
        $month = Validator::requireMonth($month);
        return $this->repository->findByMonth($month) ?? ['id' => null, 'mes_referencia' => $month, 'percentual' => 20.0, 'padrao' => true];
    }

    public function save(array $input): array
    {
        $errors = [];
        if (!Validator::month($input['mes_referencia'] ?? null)) $errors['mes_referencia'] = 'Use o formato YYYY-MM.';
        if (!is_numeric($input['percentual'] ?? null) || (float) $input['percentual'] < 0 || (float) $input['percentual'] > 100) $errors['percentual'] = 'Informe um percentual entre 0 e 100.';
        Validator::throwIf($errors);
        return $this->repository->upsert($input['mes_referencia'], round((float) $input['percentual'], 2), Database::driver() === 'sqlite');
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\Validator;
use App\Repositories\CategoryRepository;

final class CategoryService
{
    public function __construct(private readonly CategoryRepository $repository) {}

    public function list(): array { return $this->repository->all(); }

    public function create(array $input): array
    {
        $name = trim((string) ($input['nome'] ?? ''));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) $errors['nome'] = 'Informe um nome com até 100 caracteres.';
        Validator::throwIf($errors);
        if ($this->repository->existsByName($name)) {
            throw new HttpException(422, 'Dados inválidos.', ['nome' => 'Esta categoria já existe.']);
        }
        return $this->repository->create($name);
    }
}

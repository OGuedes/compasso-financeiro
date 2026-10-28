<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Request;
use App\Services\IncomeService;

final class IncomeController
{
    public function __construct(private readonly IncomeService $service) {}
    public function store(Request $request): never { JsonResponse::success($this->service->save($request->json()), 201); }
    public function index(Request $request): never { JsonResponse::success($this->service->list((string) $request->query('mes', ''))); }
}

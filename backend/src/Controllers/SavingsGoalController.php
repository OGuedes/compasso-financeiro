<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Request;
use App\Services\SavingsGoalService;

final class SavingsGoalController
{
    public function __construct(private readonly SavingsGoalService $service) {}
    public function show(Request $request): never { JsonResponse::success($this->service->get((string) $request->query('mes', ''))); }
    public function update(Request $request): never { JsonResponse::success($this->service->save($request->json())); }
}

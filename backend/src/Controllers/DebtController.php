<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Request;
use App\Core\Validator;
use App\Services\DebtService;

final class DebtController
{
    public function __construct(private readonly DebtService $service) {}
    public function index(Request $request): never { JsonResponse::success($this->service->list((string) $request->query('status', 'all'))); }
    public function store(Request $request): never { JsonResponse::success($this->service->create($request->json()), 201); }
    public function update(Request $request, array $params): never { JsonResponse::success($this->service->update(Validator::id($params['id']), $request->json())); }
    public function payInstallment(Request $request, array $params): never { JsonResponse::success($this->service->payInstallment(Validator::id($params['id']))); }
    public function destroy(Request $request, array $params): never { $this->service->delete(Validator::id($params['id'])); JsonResponse::send(null, 204); }
}

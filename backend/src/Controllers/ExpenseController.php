<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Request;
use App\Core\Validator;
use App\Services\ExpenseService;

final class ExpenseController
{
    public function __construct(private readonly ExpenseService $service) {}
    public function store(Request $request): never { JsonResponse::success($this->service->create($request->json()), 201); }
    public function index(Request $request): never { JsonResponse::success($this->service->list((string) $request->query('mes', ''), (string) $request->query('sort', 'valor'), strtolower((string) $request->query('order', 'asc')))); }
    public function update(Request $request, array $params): never { JsonResponse::success($this->service->update(Validator::id($params['id']), $request->json())); }
    public function pay(Request $request, array $params): never { JsonResponse::success($this->service->togglePaid(Validator::id($params['id']))); }
    public function destroy(Request $request, array $params): never { $this->service->delete(Validator::id($params['id'])); JsonResponse::send(null, 204); }
    public function copy(Request $request): never { JsonResponse::success($this->service->copy($request->json()), 201); }
    public function alerts(): never { JsonResponse::success($this->service->alerts()); }
}

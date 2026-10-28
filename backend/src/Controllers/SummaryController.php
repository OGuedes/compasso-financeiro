<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Request;
use App\Services\SummaryService;

final class SummaryController
{
    public function __construct(private readonly SummaryService $service) {}
    public function show(Request $request): never { JsonResponse::success($this->service->get((string) $request->query('mes', ''))); }
}

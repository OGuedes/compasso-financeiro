<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonResponse;
use App\Core\Request;
use App\Services\CategoryService;

final class CategoryController
{
    public function __construct(private readonly CategoryService $service) {}
    public function index(): never { JsonResponse::success($this->service->list()); }
    public function store(Request $request): never { JsonResponse::success($this->service->create($request->json()), 201); }
}

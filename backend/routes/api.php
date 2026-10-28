<?php

declare(strict_types=1);

use App\Config\Database;
use App\Controllers\CategoryController;
use App\Controllers\DebtController;
use App\Controllers\ExpenseController;
use App\Controllers\IncomeController;
use App\Controllers\PurchaseController;
use App\Controllers\SavingsGoalController;
use App\Controllers\SummaryController;
use App\Core\Router;
use App\Repositories\CategoryRepository;
use App\Repositories\DebtRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\IncomeRepository;
use App\Repositories\PurchaseRepository;
use App\Repositories\SavingsGoalRepository;
use App\Repositories\SummaryRepository;
use App\Services\CategoryService;
use App\Services\DebtService;
use App\Services\ExpenseService;
use App\Services\IncomeService;
use App\Services\PurchaseService;
use App\Services\SavingsGoalService;
use App\Services\SummaryService;

$pdo = Database::connection();
$categories = new CategoryRepository($pdo);

$income = new IncomeController(new IncomeService(new IncomeRepository($pdo)));
$category = new CategoryController(new CategoryService($categories));
$debt = new DebtController(new DebtService(new DebtRepository($pdo)));
$expense = new ExpenseController(new ExpenseService(new ExpenseRepository($pdo), $categories));
$purchase = new PurchaseController(new PurchaseService(new PurchaseRepository($pdo), $categories));
$goal = new SavingsGoalController(new SavingsGoalService(new SavingsGoalRepository($pdo)));
$summary = new SummaryController(new SummaryService(new SummaryRepository($pdo)));

$router = new Router();
$router->post('/api/incomes', [$income, 'store']);
$router->get('/api/incomes', [$income, 'index']);
$router->get('/api/categories', [$category, 'index']);
$router->post('/api/categories', [$category, 'store']);
$router->get('/api/debts', [$debt, 'index']);
$router->post('/api/debts', [$debt, 'store']);
$router->put('/api/debts/{id}', [$debt, 'update']);
$router->patch('/api/debts/{id}/pay-installment', [$debt, 'payInstallment']);
$router->delete('/api/debts/{id}', [$debt, 'destroy']);
$router->post('/api/expenses/copy', [$expense, 'copy']);
$router->post('/api/expenses', [$expense, 'store']);
$router->get('/api/expenses', [$expense, 'index']);
$router->put('/api/expenses/{id}', [$expense, 'update']);
$router->patch('/api/expenses/{id}/pay', [$expense, 'pay']);
$router->delete('/api/expenses/{id}', [$expense, 'destroy']);
$router->post('/api/purchases', [$purchase, 'store']);
$router->get('/api/purchases', [$purchase, 'index']);
$router->put('/api/purchases/{id}', [$purchase, 'update']);
$router->delete('/api/purchases/{id}', [$purchase, 'destroy']);
$router->get('/api/savings-goal', [$goal, 'show']);
$router->put('/api/savings-goal', [$goal, 'update']);
$router->get('/api/summary', [$summary, 'show']);
$router->get('/api/alerts', [$expense, 'alerts']);

return $router;

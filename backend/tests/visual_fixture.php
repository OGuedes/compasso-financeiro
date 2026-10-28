<?php

declare(strict_types=1);

$path = $argv[1] ?? sys_get_temp_dir() . '/controle_financeiro_visual.sqlite';
@unlink($path);
$pdo = new PDO("sqlite:{$path}");
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec(<<<'SQL'
CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL UNIQUE);
CREATE TABLE incomes (id INTEGER PRIMARY KEY AUTOINCREMENT, descricao TEXT NOT NULL, valor NUMERIC NOT NULL, mes_referencia TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(descricao, mes_referencia));
CREATE TABLE expenses (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, valor NUMERIC NOT NULL, pago INTEGER NOT NULL DEFAULT 0, dia_vencimento INTEGER, categoria_id INTEGER, mes_referencia TEXT NOT NULL, recorrente INTEGER NOT NULL DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(categoria_id) REFERENCES categories(id) ON DELETE SET NULL);
CREATE TABLE purchases (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, valor NUMERIC NOT NULL, data TEXT NOT NULL, categoria_id INTEGER, observacao TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(categoria_id) REFERENCES categories(id) ON DELETE SET NULL);
CREATE TABLE savings_goals (id INTEGER PRIMARY KEY AUTOINCREMENT, mes_referencia TEXT NOT NULL UNIQUE, percentual NUMERIC NOT NULL DEFAULT 20);
SQL);

$month = date('Y-m');
$tomorrow = new DateTimeImmutable('tomorrow');
$pdo->exec("INSERT INTO categories (nome) VALUES ('Moradia'), ('Alimentação'), ('Transporte'), ('Lazer')");
$income = $pdo->prepare('INSERT INTO incomes (descricao, valor, mes_referencia) VALUES (?, ?, ?)');
$income->execute(['Salário', 5200, $month]);
$expense = $pdo->prepare('INSERT INTO expenses (nome, valor, pago, dia_vencimento, categoria_id, mes_referencia, recorrente) VALUES (?, ?, ?, ?, ?, ?, ?)');
$expense->execute(['Aluguel', 1650, 1, 5, 1, $month, 1]);
$expense->execute(['Internet', 130, 0, (int) $tomorrow->format('d'), 1, $tomorrow->format('Y-m'), 1]);
$expense->execute(['Transporte', 320, 0, 20, 3, $month, 1]);
$purchase = $pdo->prepare('INSERT INTO purchases (nome, valor, data, categoria_id, observacao) VALUES (?, ?, ?, ?, ?)');
$purchase->execute(['Supermercado', 387.45, "{$month}-08", 2, 'Compra do mês']);
$purchase->execute(['Cinema', 64, "{$month}-17", 4, null]);
$goal = $pdo->prepare('INSERT INTO savings_goals (mes_referencia, percentual) VALUES (?, ?)');
$goal->execute([$month, 20]);
echo $path;

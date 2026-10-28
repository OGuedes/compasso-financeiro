USE controle_financeiro;

INSERT INTO categories (nome) VALUES
    ('Moradia'),
    ('Alimentação'),
    ('Transporte'),
    ('Saúde'),
    ('Lazer')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

INSERT INTO incomes (descricao, valor, mes_referencia) VALUES
    ('Salário', 5200.00, '2026-10')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO expenses (nome, valor, pago, dia_vencimento, categoria_id, mes_referencia, recorrente) VALUES
    ('Aluguel', 1650.00, 1, 5, (SELECT id FROM categories WHERE nome = 'Moradia'), '2026-10', 1),
    ('Internet', 130.00, 0, 12, (SELECT id FROM categories WHERE nome = 'Moradia'), '2026-10', 1),
    ('Plano de saúde', 420.00, 0, 18, (SELECT id FROM categories WHERE nome = 'Saúde'), '2026-10', 1);

INSERT INTO purchases (nome, valor, data, categoria_id, observacao) VALUES
    ('Supermercado', 387.45, '2026-10-08', (SELECT id FROM categories WHERE nome = 'Alimentação'), 'Compra do mês'),
    ('Cinema', 64.00, '2026-10-17', (SELECT id FROM categories WHERE nome = 'Lazer'), NULL);

INSERT INTO savings_goals (mes_referencia, percentual) VALUES ('2026-10', 20.00)
ON DUPLICATE KEY UPDATE percentual = VALUES(percentual);

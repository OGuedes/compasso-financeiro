PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL COLLATE NOCASE UNIQUE
);

CREATE TABLE IF NOT EXISTS incomes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    descricao TEXT NOT NULL,
    valor NUMERIC NOT NULL CHECK (valor >= 0),
    mes_referencia TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (descricao, mes_referencia)
);

CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    valor NUMERIC NOT NULL CHECK (valor >= 0),
    pago INTEGER NOT NULL DEFAULT 0,
    dia_vencimento INTEGER,
    categoria_id INTEGER,
    mes_referencia TEXT NOT NULL,
    recorrente INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categories (id) ON DELETE SET NULL,
    CHECK (dia_vencimento IS NULL OR dia_vencimento BETWEEN 1 AND 31)
);

CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    valor NUMERIC NOT NULL CHECK (valor >= 0),
    data TEXT NOT NULL,
    categoria_id INTEGER,
    observacao TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categories (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS savings_goals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mes_referencia TEXT NOT NULL UNIQUE,
    percentual NUMERIC NOT NULL DEFAULT 20 CHECK (percentual BETWEEN 0 AND 100)
);

CREATE TABLE IF NOT EXISTS debts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    credor TEXT,
    valor_total NUMERIC NOT NULL CHECK (valor_total > 0),
    total_parcelas INTEGER NOT NULL DEFAULT 1 CHECK (total_parcelas >= 1),
    parcelas_pagas INTEGER NOT NULL DEFAULT 0,
    valor_pago NUMERIC NOT NULL DEFAULT 0,
    dia_vencimento INTEGER,
    observacao TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (parcelas_pagas BETWEEN 0 AND total_parcelas),
    CHECK (valor_pago BETWEEN 0 AND valor_total),
    CHECK (dia_vencimento IS NULL OR dia_vencimento BETWEEN 1 AND 31)
);

CREATE INDEX IF NOT EXISTS idx_incomes_mes ON incomes (mes_referencia);
CREATE INDEX IF NOT EXISTS idx_expenses_mes ON expenses (mes_referencia);
CREATE INDEX IF NOT EXISTS idx_expenses_alertas ON expenses (pago, mes_referencia, dia_vencimento);
CREATE INDEX IF NOT EXISTS idx_purchases_data ON purchases (data);
CREATE INDEX IF NOT EXISTS idx_debts_status ON debts (parcelas_pagas, total_parcelas);

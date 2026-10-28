CREATE DATABASE IF NOT EXISTS controle_financeiro
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE controle_financeiro;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    CONSTRAINT uq_categories_nome UNIQUE (nome)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS incomes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(150) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    mes_referencia CHAR(7) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_incomes_descricao_mes UNIQUE (descricao, mes_referencia),
    CONSTRAINT chk_incomes_valor CHECK (valor >= 0),
    CONSTRAINT chk_incomes_mes CHECK (
        mes_referencia REGEXP '^[0-9]{4}-(0[1-9]|1[0-2])$'
    ),
    INDEX idx_incomes_mes (mes_referencia)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    pago BOOLEAN NOT NULL DEFAULT FALSE,
    dia_vencimento TINYINT UNSIGNED NULL,
    categoria_id BIGINT UNSIGNED NULL,
    mes_referencia CHAR(7) NOT NULL,
    recorrente BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_categoria
        FOREIGN KEY (categoria_id) REFERENCES categories (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_expenses_valor CHECK (valor >= 0),
    CONSTRAINT chk_expenses_vencimento CHECK (
        dia_vencimento IS NULL OR dia_vencimento BETWEEN 1 AND 31
    ),
    CONSTRAINT chk_expenses_mes CHECK (
        mes_referencia REGEXP '^[0-9]{4}-(0[1-9]|1[0-2])$'
    ),
    INDEX idx_expenses_mes (mes_referencia),
    INDEX idx_expenses_categoria (categoria_id),
    INDEX idx_expenses_alertas (pago, mes_referencia, dia_vencimento),
    INDEX idx_expenses_recorrentes (mes_referencia, recorrente)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data DATE NOT NULL,
    categoria_id BIGINT UNSIGNED NULL,
    observacao TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_categoria
        FOREIGN KEY (categoria_id) REFERENCES categories (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_purchases_valor CHECK (valor >= 0),
    INDEX idx_purchases_data (data),
    INDEX idx_purchases_categoria (categoria_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS savings_goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mes_referencia CHAR(7) NOT NULL,
    percentual DECIMAL(5, 2) NOT NULL DEFAULT 20.00,
    CONSTRAINT uq_savings_goals_mes UNIQUE (mes_referencia),
    CONSTRAINT chk_savings_goals_percentual CHECK (
        percentual BETWEEN 0 AND 100
    ),
    CONSTRAINT chk_savings_goals_mes CHECK (
        mes_referencia REGEXP '^[0-9]{4}-(0[1-9]|1[0-2])$'
    )
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS debts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    credor VARCHAR(150) NULL,
    valor_total DECIMAL(10, 2) NOT NULL,
    total_parcelas SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    parcelas_pagas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    valor_pago DECIMAL(10, 2) NOT NULL DEFAULT 0,
    dia_vencimento TINYINT UNSIGNED NULL,
    observacao TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_debts_valor_total CHECK (valor_total > 0),
    CONSTRAINT chk_debts_total_parcelas CHECK (total_parcelas >= 1),
    CONSTRAINT chk_debts_parcelas_pagas CHECK (parcelas_pagas <= total_parcelas),
    CONSTRAINT chk_debts_valor_pago CHECK (valor_pago BETWEEN 0 AND valor_total),
    CONSTRAINT chk_debts_vencimento CHECK (
        dia_vencimento IS NULL OR dia_vencimento BETWEEN 1 AND 31
    ),
    INDEX idx_debts_status (parcelas_pagas, total_parcelas),
    INDEX idx_debts_vencimento (dia_vencimento)
) ENGINE=InnoDB;

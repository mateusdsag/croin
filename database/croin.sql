-- ====================================
-- CROIN — Database Schema
-- ====================================

CREATE DATABASE IF NOT EXISTS croin
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE croin;

-- USUÁRIOS
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120)  NOT NULL,
    email      VARCHAR(120)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PORTFOLIO
CREATE TABLE IF NOT EXISTS portfolio (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    coin_symbol VARCHAR(20)  NOT NULL,
    coin_name   VARCHAR(100) NOT NULL,
    quantity    DECIMAL(24,8) NOT NULL DEFAULT 0,
    buy_price   DECIMAL(24,8) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WATCHLIST
CREATE TABLE IF NOT EXISTS watchlist (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT         NOT NULL,
    coin_symbol VARCHAR(20) NOT NULL,
    coin_name   VARCHAR(100) NOT NULL DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_symbol (user_id, coin_symbol),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
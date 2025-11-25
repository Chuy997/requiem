-- database/schema.sql

CREATE DATABASE IF NOT EXISTS requiem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE requiem;

-- Tabla de tipos de cambio (ingresados manualmente por admin desde SAFE)
CREATE TABLE exchange_rates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    month_year DATE NOT NULL COMMENT 'Primer día del mes (ej. 2025-10-01)',
    usd_to_mxn DECIMAL(10,6) NOT NULL CHECK (usd_to_mxn > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month (month_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios del área de ingeniería (mínimo necesario para MVP)
-- NOTA: la autenticación real puede integrarse después con LDAP o tabla propia
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla principal de NREs
CREATE TABLE nres (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nre_number VARCHAR(20) NOT NULL UNIQUE COMMENT 'Ej. ZL25110301',
    requester_id INT UNSIGNED NOT NULL,
    item_description TEXT NOT NULL,
    item_code VARCHAR(50) DEFAULT NULL,
    project_code VARCHAR(20) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price_usd DECIMAL(12,2) NOT NULL,
    unit_price_mxn DECIMAL(12,2) NOT NULL,
    subtotal_usd DECIMAL(12,2) AS (quantity * unit_price_usd) STORED,
    subtotal_mxn DECIMAL(12,2) AS (quantity * unit_price_mxn) STORED,
    tax_rate DECIMAL(5,4) NOT NULL DEFAULT 0.1600,
    total_usd DECIMAL(12,2) AS (subtotal_usd * (1 + tax_rate)) STORED,
    total_mxn DECIMAL(12,2) AS (subtotal_mxn * (1 + tax_rate)) STORED,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    needed_date DATE NOT NULL,
    reason TEXT DEFAULT NULL,
    quotation_filename VARCHAR(255) NOT NULL COMMENT 'Nombre seguro del archivo en uploads/quotations/',
    purchase_request_pdf VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del PDF generado en uploads/pdfs/',
    status ENUM('Draft', 'Approved', 'In Process', 'Arrived', 'Cancelled') NOT NULL DEFAULT 'Draft',
    approval_signed_date DATE DEFAULT NULL COMMENT 'Fecha en que se entrega el formato firmado',
    estimated_arrival_date DATE GENERATED ALWAYS AS (DATE_ADD(approval_signed_date, INTERVAL 14 DAY)) VIRTUAL,
    actual_arrival_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_requester (requester_id),
    INDEX idx_approval_date (approval_signed_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
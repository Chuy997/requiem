-- Script de verificación de base de datos para el sistema NRE
-- Fecha: 2025-12-02

-- Verificar que la tabla users tiene la columna is_admin
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' 
  AND TABLE_SCHEMA = DATABASE()
  AND COLUMN_NAME = 'is_admin';

-- Verificar estructura de la tabla nres
DESCRIBE nres;

-- Verificar estructura de la tabla exchange_rates
DESCRIBE exchange_rates;

-- Listar todos los usuarios y sus roles
SELECT 
    id,
    username,
    email,
    full_name,
    is_admin,
    created_at
FROM users
ORDER BY is_admin DESC, id ASC;

-- Verificar tipos de cambio configurados
SELECT 
    period,
    rate_mxn_per_usd,
    created_at,
    updated_at
FROM exchange_rates
ORDER BY period DESC;

-- Verificar el tipo de cambio del mes actual (Diciembre 2025)
SELECT 
    period,
    rate_mxn_per_usd,
    created_at
FROM exchange_rates
WHERE period = '202512';

-- Si no existe el tipo de cambio del mes actual, puedes insertarlo con:
-- INSERT INTO exchange_rates (period, rate_mxn_per_usd) 
-- VALUES ('202512', 20.50);  -- Ajusta el valor según el tipo de cambio real

-- Verificar NREs por estado
SELECT 
    status,
    COUNT(*) as cantidad,
    SUM(unit_price_mxn * quantity) as total_mxn
FROM nres
GROUP BY status
ORDER BY 
    FIELD(status, 'Draft', 'Approved', 'In Process', 'Arrived', 'Cancelled');

-- Verificar NREs por usuario
SELECT 
    u.full_name,
    u.email,
    COUNT(n.id) as total_nres,
    SUM(CASE WHEN n.status = 'Draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN n.status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN n.status = 'In Process' THEN 1 ELSE 0 END) as in_process,
    SUM(CASE WHEN n.status = 'Arrived' THEN 1 ELSE 0 END) as arrived,
    SUM(CASE WHEN n.status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
FROM users u
LEFT JOIN nres n ON u.id = n.requester_id
GROUP BY u.id, u.full_name, u.email
ORDER BY total_nres DESC;

-- database/migrations/add_password_hash.sql
-- Migración para agregar campo password_hash a la tabla users

USE requiem;

-- Agregar columna password_hash si no existe
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) DEFAULT NULL AFTER email;

-- Crear contraseña por defecto para usuarios existentes (ChangeMe123!)
-- IMPORTANTE: Los usuarios deben cambiar esta contraseña después del primer login
UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password_hash IS NULL OR password_hash = '';

-- La contraseña por defecto es: ChangeMe123!
-- Hash generado con: password_hash('ChangeMe123!', PASSWORD_BCRYPT)

-- Verificar que todos los usuarios tengan contraseña
SELECT id, username, email, 
       CASE 
           WHEN password_hash IS NOT NULL THEN 'OK'
           ELSE 'MISSING'
       END as password_status
FROM users;

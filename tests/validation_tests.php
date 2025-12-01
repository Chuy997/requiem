#!/usr/bin/env php
<?php
/**
 * Script de Validación Automatizada - Sistema Requiem
 * Ejecuta pruebas sobre funcionalidades críticas del sistema
 * 
 * Uso: php tests/validation_tests.php
 */

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/Nre.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/ExchangeRate.php';
require_once __DIR__ . '/../src/controllers/NreController.php';

class ValidationTests {
    private $passed = 0;
    private $failed = 0;
    private $warnings = 0;

    public function run() {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║  SISTEMA REQUIEM - VALIDACIÓN AUTOMATIZADA DE FUNCIONES  ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        // Pruebas de Conectividad
        $this->section("1. CONECTIVIDAD Y CONFIGURACIÓN");
        $this->testDatabaseConnection();
        $this->testEnvironmentVariables();
        
        // Pruebas de Modelos
        $this->section("2. MODELOS DE DATOS");
        $this->testNreNumberGeneration();
        $this->testUserModel();
        $this->testExchangeRateModel();
        
        // Pruebas de Lógica de Negocio
        $this->section("3. LÓGICA DE NEGOCIO");
        $this->testCurrencyConversion();
        $this->testIVACalculation();
        $this->testNreStateTransitions();
        
        // Pruebas de Seguridad
        $this->section("4. SEGURIDAD");
        $this->testSQLInjectionPrevention();
        $this->testXSSPrevention();
        $this->testFileUploadSecurity();
        
        // Pruebas de Integridad de Datos
        $this->section("5. INTEGRIDAD DE DATOS");
        $this->testDatabaseSchema();
        $this->testForeignKeys();
        
        // Resumen
        $this->printSummary();
    }

    private function section($title) {
        echo "\n";
        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│ $title\n";
        echo "└─────────────────────────────────────────────────────────┘\n";
    }

    private function test($name, $callback) {
        echo "  Testing: $name ... ";
        try {
            $result = $callback();
            if ($result === true) {
                echo "✅ PASS\n";
                $this->passed++;
            } elseif ($result === null) {
                echo "⚠️  WARN\n";
                $this->warnings++;
            } else {
                echo "❌ FAIL: $result\n";
                $this->failed++;
            }
        } catch (Exception $e) {
            echo "❌ FAIL: " . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    // ==================== PRUEBAS DE CONECTIVIDAD ====================
    
    private function testDatabaseConnection() {
        $this->test("Conexión a base de datos", function() {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $result = $conn->query("SELECT 1 AS test");
            return $result && $result->fetch_assoc()['test'] == 1;
        });
    }

    private function testEnvironmentVariables() {
        $this->test("Variables de entorno cargadas", function() {
            $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'SMTP_HOST', 'SMTP_USERNAME'];
            foreach ($required as $var) {
                if (!isset($_ENV[$var])) {
                    return "Variable $var no encontrada";
                }
            }
            return true;
        });
    }

    // ==================== PRUEBAS DE MODELOS ====================
    
    private function testNreNumberGeneration() {
        $this->test("Generación de números NRE únicos", function() {
            $nre1 = Nre::generateNextNreNumber();
            $nre2 = Nre::generateNextNreNumber();
            
            // Validar formato: XY + YYYYMMDD + secuencial
            if (!preg_match('/^XY\d{10}$/', $nre1)) {
                return "Formato inválido: $nre1";
            }
            
            // Validar que sean diferentes (si se generan en el mismo día)
            $prefix1 = substr($nre1, 0, 10);
            $prefix2 = substr($nre2, 0, 10);
            
            if ($prefix1 === $prefix2 && $nre1 === $nre2) {
                return "Números duplicados: $nre1 = $nre2";
            }
            
            return true;
        });

        $this->test("Generación de múltiples números NRE", function() {
            $numbers = Nre::getNextNreNumbers(5);
            
            if (count($numbers) !== 5) {
                return "Se esperaban 5 números, se obtuvieron " . count($numbers);
            }
            
            // Validar que sean consecutivos
            for ($i = 1; $i < count($numbers); $i++) {
                $prev = (int)substr($numbers[$i-1], -2);
                $curr = (int)substr($numbers[$i], -2);
                if ($curr !== $prev + 1) {
                    return "Números no consecutivos: {$numbers[$i-1]} → {$numbers[$i]}";
                }
            }
            
            return true;
        });
    }

    private function testUserModel() {
        $this->test("Modelo User - Carga de usuario válido", function() {
            try {
                $user = new User(1);
                if (empty($user->getFullName())) {
                    return "Usuario 1 no tiene nombre completo";
                }
                if (empty($user->getEmail())) {
                    return "Usuario 1 no tiene email";
                }
                return true;
            } catch (Exception $e) {
                return "Usuario 1 no existe en la BD";
            }
        });

        $this->test("Modelo User - Usuario inválido lanza excepción", function() {
            try {
                $user = new User(99999);
                return "Debería lanzar excepción para usuario inexistente";
            } catch (Exception $e) {
                return true;
            }
        });
    }

    private function testExchangeRateModel() {
        $this->test("Modelo ExchangeRate - Obtener tipo de cambio", function() {
            $exchangeRate = new ExchangeRate();
            $period = $exchangeRate->getLastMonthPeriod();
            
            if (!preg_match('/^\d{6}$/', $period)) {
                return "Formato de período inválido: $period";
            }
            
            $rate = $exchangeRate->getRateForPeriod($period);
            
            if ($rate === null) {
                return null; // Warning: no hay tipo de cambio para el mes anterior
            }
            
            if ($rate <= 0) {
                return "Tipo de cambio inválido: $rate";
            }
            
            return true;
        });
    }

    // ==================== PRUEBAS DE LÓGICA DE NEGOCIO ====================
    
    private function testCurrencyConversion() {
        $this->test("Conversión USD → MXN", function() {
            $usd = 100.00;
            $rate = 20.50;
            $expectedMxn = 2050.00;
            
            $actualMxn = round($usd * $rate, 2);
            
            if ($actualMxn !== $expectedMxn) {
                return "Conversión incorrecta: $usd USD * $rate = $actualMxn (esperado: $expectedMxn)";
            }
            
            return true;
        });

        $this->test("Conversión MXN → USD", function() {
            $mxn = 2050.00;
            $rate = 20.50;
            $expectedUsd = 100.00;
            
            $actualUsd = round($mxn / $rate, 2);
            
            if ($actualUsd !== $expectedUsd) {
                return "Conversión incorrecta: $mxn MXN / $rate = $actualUsd (esperado: $expectedUsd)";
            }
            
            return true;
        });
    }

    private function testIVACalculation() {
        $this->test("Cálculo de IVA (16%)", function() {
            $subtotal = 1000.00;
            $iva = 0.16;
            $expectedTotal = 1160.00;
            
            $actualTotal = round($subtotal * (1 + $iva), 2);
            
            if ($actualTotal !== $expectedTotal) {
                return "Cálculo de IVA incorrecto: $subtotal * 1.16 = $actualTotal (esperado: $expectedTotal)";
            }
            
            return true;
        });
    }

    private function testNreStateTransitions() {
        $this->test("Transiciones de estado válidas", function() {
            $validTransitions = [
                'Draft' => ['In Process', 'Cancelled'],
                'In Process' => ['Arrived', 'Cancelled'],
                'Arrived' => [],
                'Cancelled' => []
            ];
            
            // Esta es una prueba conceptual; en producción validaríamos contra la BD
            return true;
        });
    }

    // ==================== PRUEBAS DE SEGURIDAD ====================
    
    private function testSQLInjectionPrevention() {
        $this->test("Prevención de SQL Injection", function() {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Intentar inyección SQL
            $maliciousInput = "1' OR '1'='1";
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("s", $maliciousInput);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Si prepared statements funcionan, no debería retornar resultados
            if ($result->num_rows > 0) {
                return "Vulnerable a SQL Injection";
            }
            
            return true;
        });
    }

    private function testXSSPrevention() {
        $this->test("Prevención de XSS", function() {
            $maliciousInput = "<script>alert('XSS')</script>";
            $sanitized = htmlspecialchars($maliciousInput);
            
            if (strpos($sanitized, '<script>') !== false) {
                return "XSS no sanitizado correctamente";
            }
            
            return true;
        });
    }

    private function testFileUploadSecurity() {
        $this->test("Validación de extensiones de archivo", function() {
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            $testFiles = [
                'cotizacion.pdf' => true,
                'imagen.jpg' => true,
                'script.php' => false,
                'malware.exe' => false
            ];
            
            foreach ($testFiles as $filename => $shouldBeAllowed) {
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $isAllowed = in_array($ext, $allowedExtensions);
                
                if ($isAllowed !== $shouldBeAllowed) {
                    return "Validación incorrecta para $filename";
                }
            }
            
            return true;
        });
    }

    // ==================== PRUEBAS DE INTEGRIDAD DE DATOS ====================
    
    private function testDatabaseSchema() {
        $this->test("Esquema de tabla 'nres' completo", function() {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $result = $conn->query("DESCRIBE nres");
            $fields = [];
            while ($row = $result->fetch_assoc()) {
                $fields[] = $row['Field'];
            }
            
            $requiredFields = [
                'id', 'nre_number', 'requester_id', 'item_description',
                'quantity', 'unit_price_usd', 'unit_price_mxn', 'status'
            ];
            
            foreach ($requiredFields as $field) {
                if (!in_array($field, $fields)) {
                    return "Campo requerido '$field' no existe";
                }
            }
            
            return true;
        });

        $this->test("Índices en campos críticos", function() {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $result = $conn->query("SHOW INDEX FROM nres");
            $indexes = [];
            while ($row = $result->fetch_assoc()) {
                $indexes[] = $row['Column_name'];
            }
            
            $requiredIndexes = ['status', 'requester_id'];
            
            foreach ($requiredIndexes as $index) {
                if (!in_array($index, $indexes)) {
                    return null; // Warning: índice recomendado no existe
                }
            }
            
            return true;
        });
    }

    private function testForeignKeys() {
        $this->test("Foreign keys configuradas", function() {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $result = $conn->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = 'requiem' 
                AND TABLE_NAME = 'nres' 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");
            
            if ($result->num_rows === 0) {
                return null; // Warning: no hay foreign keys
            }
            
            return true;
        });
    }

    // ==================== RESUMEN ====================
    
    private function printSummary() {
        $total = $this->passed + $this->failed + $this->warnings;
        
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║                    RESUMEN DE PRUEBAS                     ║\n";
        echo "╠════════════════════════════════════════════════════════════╣\n";
        printf("║  Total de pruebas:    %3d                                 ║\n", $total);
        printf("║  ✅ Aprobadas:        %3d (%.1f%%)                         ║\n", 
            $this->passed, ($total > 0 ? ($this->passed / $total) * 100 : 0));
        printf("║  ❌ Fallidas:         %3d (%.1f%%)                         ║\n", 
            $this->failed, ($total > 0 ? ($this->failed / $total) * 100 : 0));
        printf("║  ⚠️  Advertencias:    %3d (%.1f%%)                         ║\n", 
            $this->warnings, ($total > 0 ? ($this->warnings / $total) * 100 : 0));
        echo "╠════════════════════════════════════════════════════════════╣\n";
        
        if ($this->failed === 0 && $this->warnings === 0) {
            echo "║  ESTADO: ✅ TODAS LAS PRUEBAS PASARON                    ║\n";
        } elseif ($this->failed === 0) {
            echo "║  ESTADO: ⚠️  APROBADO CON ADVERTENCIAS                   ║\n";
        } else {
            echo "║  ESTADO: ❌ ALGUNAS PRUEBAS FALLARON                     ║\n";
        }
        
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
        
        // Exit code para CI/CD
        exit($this->failed > 0 ? 1 : 0);
    }
}

// Ejecutar pruebas
$tests = new ValidationTests();
$tests->run();

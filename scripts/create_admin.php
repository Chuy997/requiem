<?php
// scripts/create_admin.php
// Script para crear el usuario administrador inicial

require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/models/User.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         CREAR USUARIO ADMINISTRADOR - SISTEMA REQUIEM       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Datos del admin
$adminEmail = 'admin@xinya-la.com';
$adminPassword = 'Admin123!';
$adminName = 'Administrador del Sistema';

try {
    // Verificar si ya existe un admin
    $existingAdmin = User::findByEmail($adminEmail);
    
    if ($existingAdmin) {
        echo "⚠️  El usuario admin ya existe.\n";
        echo "Email: $adminEmail\n";
        echo "ID: " . $existingAdmin->getId() . "\n\n";
        
        // Preguntar si quiere resetear la contraseña
        echo "¿Desea resetear la contraseña? (s/n): ";
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        
        if(trim($line) == 's' || trim($line) == 'S'){
            $userId = $existingAdmin->getId();
            User::updateUser($userId, $adminEmail, $adminName, true, $adminPassword);
            echo "\n✅ Contraseña reseteada exitosamente.\n";
            echo "Nueva contraseña: $adminPassword\n\n";
        } else {
            echo "\n❌ Operación cancelada.\n\n";
        }
        
        fclose($handle);
    } else {
        // Crear nuevo admin
        $userId = User::createUser($adminEmail, $adminPassword, $adminName, true);
        
        echo "✅ Usuario administrador creado exitosamente!\n\n";
        echo "┌────────────────────────────────────────────────────────────┐\n";
        echo "│ CREDENCIALES DE ACCESO                                    │\n";
        echo "├────────────────────────────────────────────────────────────┤\n";
        echo "│ ID:        $userId                                            │\n";
        echo "│ Email:     $adminEmail                          │\n";
        echo "│ Password:  $adminPassword                                     │\n";
        echo "│ Rol:       ADMINISTRADOR                                  │\n";
        echo "└────────────────────────────────────────────────────────────┘\n\n";
        
        echo "⚠️  IMPORTANTE:\n";
        echo "   - Cambia esta contraseña después del primer login\n";
        echo "   - No compartas estas credenciales\n";
        echo "   - Accede al sistema en: http://localhost/requiem/public/login.php\n\n";
    }
    
    // Mostrar todos los usuarios
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "USUARIOS EXISTENTES EN EL SISTEMA:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $users = User::getAllUsers();
    
    foreach ($users as $user) {
        $role = $user['is_admin'] ? 'ADMIN' : 'ENGINEER';
        $roleIcon = $user['is_admin'] ? '👑' : '👤';
        
        echo "$roleIcon ID: {$user['id']} | {$user['full_name']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Rol: $role\n";
        echo "   Creado: {$user['created_at']}\n";
        echo "   ───────────────────────────────────────────────────────────\n";
    }
    
    echo "\n✅ Script completado exitosamente.\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

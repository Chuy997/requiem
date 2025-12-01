<?php
// set_password.php - Solo para uso temporal

require_once __DIR__ . '/../src/config/db.php';

$db = Database::getInstance();
$conn = $db->getConnection();


$password = 'Monday.03'; 
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = 1");
$stmt->bind_param("s", $hash);
if ($stmt->execute()) {
    echo "✅ Contraseña de ID=1 actualizada correctamente.<br>";
    echo "Contraseña en texto claro (guárdala): " . htmlspecialchars($password);
} else {
    echo "❌ Error: " . $conn->error;
}
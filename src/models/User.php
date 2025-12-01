<?php
// src/models/User.php

require_once __DIR__ . '/../config/db.php';

// Constantes de roles (cumpliendo con el requerimiento de usar define)
if (!defined('ROLE_ENGINEER')) {
    define('ROLE_ENGINEER', 'engineer');
}
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}

class User {
    private $id;
    private $username;
    private $email;
    private $full_name;
    private $role;

    public function __construct($id = null) {
        if ($id !== null) {
            $this->loadById($id);
        }
    }

    private function loadById($id) {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id, username, email, full_name, is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->id = (int)$row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            // Mapeo de is_admin (boolean) a rol
            $this->role = $row['is_admin'] ? ROLE_ADMIN : ROLE_ENGINEER;
        } else {
            throw new Exception("User not found");
        }
    }

    public static function findByEmail(string $email): ?self {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return new self((int)$row['id']);
        }
        return null;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getFullName(): string { return $this->full_name; }
    public function getRole(): string { return $this->role; }
    public function isAdmin(): bool { return $this->role === ROLE_ADMIN; }

    // Verifica si el usuario pertenece al equipo de ingeniería permitido (IDs 1,2,3)
    public function isAuthorizedEngineer(): bool {
        return in_array($this->id, [1, 2, 3], true);
    }

    // En el MVP, cualquier usuario autorizado (1,2,3) puede crear NREs
    public function canCreateNre(): bool {
        return $this->isAuthorizedEngineer();
    }

    // ==================== MÉTODOS DE ADMINISTRACIÓN ====================
    
    /**
     * Verifica la contraseña del usuario
     */
    public function verifyPassword(string $password): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return password_verify($password, $row['password_hash']);
        }
        
        return false;
    }
    
    /**
     * Crea un nuevo usuario (solo admin)
     */
    public static function createUser(string $email, string $password, string $fullName, bool $isAdmin = false): int {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar que el email no exista
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("El email ya está registrado");
        }
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $username = explode('@', $email)[0];
        
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password_hash, full_name, is_admin)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", $username, $email, $passwordHash, $fullName, $isAdmin);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al crear usuario: " . $conn->error);
        }
        
        return $conn->insert_id;
    }
    
    /**
     * Actualiza un usuario existente
     */
    public static function updateUser(int $id, string $email, string $fullName, bool $isAdmin, ?string $newPassword = null): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar que el email no esté en uso por otro usuario
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("El email ya está en uso por otro usuario");
        }
        
        if ($newPassword) {
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("
                UPDATE users 
                SET email = ?, full_name = ?, is_admin = ?, password_hash = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssisi", $email, $fullName, $isAdmin, $passwordHash, $id);
        } else {
            $stmt = $conn->prepare("
                UPDATE users 
                SET email = ?, full_name = ?, is_admin = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssii", $email, $fullName, $isAdmin, $id);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Elimina un usuario (soft delete o hard delete)
     */
    public static function deleteUser(int $id): bool {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // No permitir eliminar usuario con ID 1 (admin principal)
        if ($id === 1) {
            throw new Exception("No se puede eliminar el usuario administrador principal");
        }
        
        // Verificar que no tenga NREs asociados
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM nres WHERE requester_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            throw new Exception("No se puede eliminar un usuario con NREs asociados");
        }
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Obtiene todos los usuarios
     */
    public static function getAllUsers(): array {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $result = $conn->query("
            SELECT id, username, email, full_name, is_admin, created_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obtiene un usuario por ID (array asociativo)
     */
    public static function getUserById(int $id): ?array {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT id, username, email, full_name, is_admin, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Cambia la contraseña del usuario actual
     */
    public function changePassword(string $currentPassword, string $newPassword): bool {
        if (!$this->verifyPassword($currentPassword)) {
            throw new Exception("Contraseña actual incorrecta");
        }
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $passwordHash, $this->id);
        
        return $stmt->execute();
    }
}
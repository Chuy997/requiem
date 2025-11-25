<?php
// src/models/User.php

require_once __DIR__ . '/../config/db.php';

class User {
    private $id;
    private $username;
    private $email;
    private $full_name;
    private $is_admin;

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
            $this->is_admin = (bool)$row['is_admin'];
        } else {
            throw new Exception("User not found");
        }
    }

    // Método estático para obtener un usuario por email (útil al crear NRE)
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
    public function isAdmin(): bool { return $this->is_admin; }

    // Verifica si el usuario pertenece al área de ingeniería (en MVP, todos los usuarios en la tabla son de ingeniería)
    public function canCreateNre(): bool {
        // En este MVP, todos los usuarios registrados pueden crear NREs
        return $this->id !== null;
    }
}
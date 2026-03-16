<?php

namespace App\Models;

use App\Config\Database;
use mysqli;

class User {
    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    /**
     * Get all users
     */
    public function getAll(int $limit = 10, int $offset = 0): array {
        $stmt = $this->db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get total count of users
     */
    public function getCount(): int {
        $result = $this->db->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Get user by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Create a new user
     */
    public function create(array $data): ?int {
        $stmt = $this->db->prepare("INSERT INTO users (name, email, phone) VALUES (?, ?, ?)");
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $stmt->bind_param('sss', $name, $email, $phone);

        if ($stmt->execute()) {
            return (int)$this->db->insert_id;
        }
        return null;
    }

    /**
     * Update an existing user
     */
    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $stmt->bind_param('sssi', $name, $email, $phone, $id);
        return $stmt->execute();
    }

    /**
     * Delete a user
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /**
     * Check if email exists (for validation)
     */
    public function emailExists(string $email, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param('si', $email, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return ((int)($row['count'] ?? 0)) > 0;
    }

    /**
     * Search users by name or email
     */
    public function search(string $query, int $limit = 10, int $offset = 0): array {
        $searchTerm = '%' . $query . '%';
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->bind_param('ssii', $searchTerm, $searchTerm, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

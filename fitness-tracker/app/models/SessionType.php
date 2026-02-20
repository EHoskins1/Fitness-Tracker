<?php
/**
 * Session Type Model
 * 
 * Handles session type data operations.
 */

namespace App\Models;

use Database;
use App\Utils\Logger;

class SessionType {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get all session types for a user (including defaults)
     */
    public function getAll(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, name, icon, is_default
            FROM session_types
            WHERE user_id IS NULL OR user_id = :user_id
            ORDER BY is_default DESC, name ASC
        ");
        
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get default session types
     */
    public function getDefaults(): array {
        $stmt = $this->db->prepare("
            SELECT id, name, icon
            FROM session_types
            WHERE is_default = TRUE
            ORDER BY name ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a custom session type
     */
    public function create(int $userId, string $name, ?string $icon = null): ?int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO session_types (user_id, name, icon, is_default)
                VALUES (:user_id, :name, :icon, FALSE)
            ");
            
            $stmt->execute([
                'user_id' => $userId,
                'name' => $name,
                'icon' => $icon
            ]);
            
            $id = (int) $this->db->lastInsertId();
            Logger::dataChange('session_types', 'INSERT', $id, $userId);
            
            return $id;
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return null;
        }
    }

    /**
     * Delete a custom session type
     */
    public function delete(int $id, int $userId): bool {
        try {
            // Can only delete user's own types (not defaults)
            $stmt = $this->db->prepare("
                DELETE FROM session_types
                WHERE id = :id AND user_id = :user_id AND is_default = FALSE
            ");
            
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            
            if ($stmt->rowCount() > 0) {
                Logger::dataChange('session_types', 'DELETE', $id, $userId);
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return false;
        }
    }

    /**
     * Check if a session type name exists
     */
    public function exists(string $name, int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM session_types
            WHERE name = :name AND (user_id IS NULL OR user_id = :user_id)
        ");
        
        $stmt->execute(['name' => $name, 'user_id' => $userId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get session type by name
     */
    public function findByName(string $name, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, name, icon, is_default
            FROM session_types
            WHERE name = :name AND (user_id IS NULL OR user_id = :user_id)
            LIMIT 1
        ");
        
        $stmt->execute(['name' => $name, 'user_id' => $userId]);
        $type = $stmt->fetch();
        
        return $type ?: null;
    }
}

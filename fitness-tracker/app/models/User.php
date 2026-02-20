<?php
/**
 * User Model
 * 
 * Handles user data operations.
 */

namespace App\Models;

use Database;
use App\Utils\Logger;

class User {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new user
     */
    public function create(string $username, string $password, ?string $email = null): ?int {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password_hash)
                VALUES (:name, :email, :password_hash)
            ");
            
            $stmt->execute([
                'name' => $username,
                'email' => $email,
                'password_hash' => $passwordHash
            ]);
            
            $userId = (int) $this->db->lastInsertId();
            Logger::dataChange('users', 'INSERT', $userId);
            
            return $userId;
        } catch (\PDOException $e) {
            Logger::exception($e);
            return null;
        }
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, password_hash, created_at
            FROM users
            WHERE name = :name
            LIMIT 1
        ");
        
        $stmt->execute(['name' => $username]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, name, email, created_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");
        
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM users WHERE name = :name
        ");
        $stmt->execute(['name' => $username]);
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Update password
     */
    public function updatePassword(int $userId, string $newPassword): bool {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = :password_hash
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $userId,
                'password_hash' => $passwordHash
            ]);
            
            Logger::dataChange('users', 'UPDATE_PASSWORD', $userId, $userId);
            return true;
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return false;
        }
    }

    /**
     * Create password reset token
     */
    public function createPasswordResetToken(int $userId): ?string {
        try {
            // Delete any existing tokens for this user
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            
            // Generate new token
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);
            
            $stmt = $this->db->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:user_id, :token_hash, :expires_at)
            ");
            
            $stmt->execute([
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt
            ]);
            
            Logger::info("Password reset token created", $userId);
            return $token;
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return null;
        }
    }

    /**
     * Verify password reset token
     */
    public function verifyPasswordResetToken(string $token): ?int {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $this->db->prepare("
            SELECT user_id, expires_at
            FROM password_resets
            WHERE token_hash = :token_hash
            LIMIT 1
        ");
        
        $stmt->execute(['token_hash' => $tokenHash]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return null;
        }
        
        // Check if expired
        if (strtotime($result['expires_at']) < time()) {
            $this->deletePasswordResetToken($tokenHash);
            return null;
        }
        
        return (int) $result['user_id'];
    }

    /**
     * Delete password reset token
     */
    public function deletePasswordResetToken(string $tokenHash): void {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token_hash = :token_hash");
        $stmt->execute(['token_hash' => $tokenHash]);
    }

    /**
     * Delete used reset token by user ID
     */
    public function deletePasswordResetTokenByUserId(int $userId): void {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
    }
}

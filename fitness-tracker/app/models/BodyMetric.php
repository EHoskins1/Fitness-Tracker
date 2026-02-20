<?php
/**
 * Body Metric Model
 * 
 * Handles body metrics data operations (weight, body fat, etc.).
 */

namespace App\Models;

use Database;
use App\Utils\Logger;

class BodyMetric {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new body metric entry
     */
    public function create(array $data): ?int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO body_metrics (user_id, date, weight_kg, body_fat_percent, notes)
                VALUES (:user_id, :date, :weight_kg, :body_fat_percent, :notes)
            ");
            
            $stmt->execute([
                'user_id' => $data['user_id'],
                'date' => $data['date'],
                'weight_kg' => $data['weight_kg'] ?? null,
                'body_fat_percent' => $data['body_fat_percent'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
            
            $id = (int) $this->db->lastInsertId();
            Logger::dataChange('body_metrics', 'INSERT', $id, $data['user_id']);
            
            return $id;
        } catch (\PDOException $e) {
            Logger::exception($e, $data['user_id'] ?? null);
            return null;
        }
    }

    /**
     * Get body metric by ID
     */
    public function findById(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, date, weight_kg, body_fat_percent, notes, created_at
            FROM body_metrics
            WHERE id = :id AND user_id = :user_id
            LIMIT 1
        ");
        
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $metric = $stmt->fetch();
        
        return $metric ?: null;
    }

    /**
     * Get body metrics for a user
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT id, date, weight_kg, body_fat_percent, notes, created_at
            FROM body_metrics
            WHERE user_id = :user_id
            ORDER BY date DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get latest body metric for a user
     */
    public function getLatest(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, date, weight_kg, body_fat_percent, notes, created_at
            FROM body_metrics
            WHERE user_id = :user_id
            ORDER BY date DESC
            LIMIT 1
        ");
        
        $stmt->execute(['user_id' => $userId]);
        $metric = $stmt->fetch();
        
        return $metric ?: null;
    }

    /**
     * Get body metric from a week ago
     */
    public function getWeekAgo(int $userId): ?array {
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        
        $stmt = $this->db->prepare("
            SELECT id, date, weight_kg, body_fat_percent, notes
            FROM body_metrics
            WHERE user_id = :user_id AND date <= :week_ago
            ORDER BY date DESC
            LIMIT 1
        ");
        
        $stmt->execute(['user_id' => $userId, 'week_ago' => $weekAgo]);
        $metric = $stmt->fetch();
        
        return $metric ?: null;
    }

    /**
     * Get weight history for trend analysis
     */
    public function getWeightHistory(int $userId, int $days = 90): array {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $stmt = $this->db->prepare("
            SELECT date, weight_kg
            FROM body_metrics
            WHERE user_id = :user_id 
                AND date >= :start_date 
                AND weight_kg IS NOT NULL
            ORDER BY date ASC
        ");
        
        $stmt->execute(['user_id' => $userId, 'start_date' => $startDate]);
        return $stmt->fetchAll();
    }

    /**
     * Get body fat history for trend analysis
     */
    public function getBodyFatHistory(int $userId, int $days = 90): array {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $stmt = $this->db->prepare("
            SELECT date, body_fat_percent
            FROM body_metrics
            WHERE user_id = :user_id 
                AND date >= :start_date 
                AND body_fat_percent IS NOT NULL
            ORDER BY date ASC
        ");
        
        $stmt->execute(['user_id' => $userId, 'start_date' => $startDate]);
        return $stmt->fetchAll();
    }

    /**
     * Get body metrics for a date range
     */
    public function getByDateRange(int $userId, string $startDate, string $endDate): array {
        $stmt = $this->db->prepare("
            SELECT id, date, weight_kg, body_fat_percent, notes
            FROM body_metrics
            WHERE user_id = :user_id AND date BETWEEN :start_date AND :end_date
            ORDER BY date ASC
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Delete a body metric entry
     */
    public function delete(int $id, int $userId): bool {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM body_metrics
                WHERE id = :id AND user_id = :user_id
            ");
            
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            
            if ($stmt->rowCount() > 0) {
                Logger::dataChange('body_metrics', 'DELETE', $id, $userId);
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return false;
        }
    }

    /**
     * Update a body metric entry
     */
    public function update(int $id, int $userId, array $data): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE body_metrics
                SET date = :date,
                    weight_kg = :weight_kg,
                    body_fat_percent = :body_fat_percent,
                    notes = :notes
                WHERE id = :id AND user_id = :user_id
            ");
            
            $stmt->execute([
                'id' => $id,
                'user_id' => $userId,
                'date' => $data['date'],
                'weight_kg' => $data['weight_kg'] ?? null,
                'body_fat_percent' => $data['body_fat_percent'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
            
            if ($stmt->rowCount() > 0) {
                Logger::dataChange('body_metrics', 'UPDATE', $id, $userId);
                return true;
            }
            
            return true; // No changes but valid
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return false;
        }
    }
}

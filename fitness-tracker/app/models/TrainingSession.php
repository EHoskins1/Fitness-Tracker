<?php
/**
 * Training Session Model
 * 
 * Handles training session data operations.
 */

namespace App\Models;

use Database;
use App\Utils\Logger;

class TrainingSession {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new training session
     */
    public function create(array $data): ?int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sessions (user_id, date, start_time, duration_minutes, session_type, intensity, location, notes)
                VALUES (:user_id, :date, :start_time, :duration_minutes, :session_type, :intensity, :location, :notes)
            ");
            
            $stmt->execute([
                'user_id' => $data['user_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'session_type' => $data['session_type'],
                'intensity' => $data['intensity'] ?? null,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
            
            $sessionId = (int) $this->db->lastInsertId();
            Logger::dataChange('sessions', 'INSERT', $sessionId, $data['user_id']);
            
            return $sessionId;
        } catch (\PDOException $e) {
            Logger::exception($e, $data['user_id'] ?? null);
            return null;
        }
    }

    /**
     * Add session details (key-value pairs)
     */
    public function addDetails(int $sessionId, array $details): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO session_details (session_id, detail_key, detail_value)
                VALUES (:session_id, :detail_key, :detail_value)
            ");
            
            foreach ($details as $key => $value) {
                if (!empty($key) && $value !== null && $value !== '') {
                    $stmt->execute([
                        'session_id' => $sessionId,
                        'detail_key' => $key,
                        'detail_value' => (string) $value
                    ]);
                }
            }
            
            return true;
        } catch (\PDOException $e) {
            Logger::exception($e);
            return false;
        }
    }

    /**
     * Get session by ID
     */
    public function findById(int $id, int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, date, start_time, duration_minutes, session_type, intensity, location, notes, created_at
            FROM sessions
            WHERE id = :id AND user_id = :user_id
            LIMIT 1
        ");
        
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $session = $stmt->fetch();
        
        if ($session) {
            $session['details'] = $this->getDetails($id);
        }
        
        return $session ?: null;
    }

    /**
     * Get session details
     */
    public function getDetails(int $sessionId): array {
        $stmt = $this->db->prepare("
            SELECT detail_key, detail_value
            FROM session_details
            WHERE session_id = :session_id
        ");
        
        $stmt->execute(['session_id' => $sessionId]);
        
        $details = [];
        while ($row = $stmt->fetch()) {
            $details[$row['detail_key']] = $row['detail_value'];
        }
        
        return $details;
    }

    /**
     * Get sessions for a user
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT id, date, start_time, duration_minutes, session_type, intensity, location, notes, created_at
            FROM sessions
            WHERE user_id = :user_id
            ORDER BY date DESC, start_time DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get sessions for a date range
     */
    public function getByDateRange(int $userId, string $startDate, string $endDate): array {
        $stmt = $this->db->prepare("
            SELECT id, date, start_time, duration_minutes, session_type, intensity, location, notes
            FROM sessions
            WHERE user_id = :user_id AND date BETWEEN :start_date AND :end_date
            ORDER BY date ASC, start_time ASC
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get sessions for a specific date
     */
    public function getByDate(int $userId, string $date): array {
        $stmt = $this->db->prepare("
            SELECT id, date, start_time, duration_minutes, session_type, intensity, location, notes
            FROM sessions
            WHERE user_id = :user_id AND date = :date
            ORDER BY start_time ASC
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'date' => $date
        ]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get weekly stats
     */
    public function getWeeklyStats(int $userId): array {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as session_count,
                COALESCE(SUM(duration_minutes), 0) as total_duration,
                COALESCE(AVG(intensity), 0) as avg_intensity
            FROM sessions
            WHERE user_id = :user_id AND date BETWEEN :start_date AND :end_date
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startOfWeek,
            'end_date' => $endOfWeek
        ]);
        
        $stats = $stmt->fetch();
        
        // Get run distance from details
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(CAST(sd.detail_value AS DECIMAL(10,2))), 0) as total_distance
            FROM sessions s
            JOIN session_details sd ON s.id = sd.session_id
            WHERE s.user_id = :user_id 
                AND s.date BETWEEN :start_date AND :end_date
                AND sd.detail_key = 'distance_km'
        ");
        
        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startOfWeek,
            'end_date' => $endOfWeek
        ]);
        
        $distance = $stmt->fetch();
        $stats['total_distance_km'] = $distance['total_distance'] ?? 0;
        
        return $stats;
    }

    /**
     * Get monthly training volume
     */
    public function getMonthlyVolume(int $userId, int $months = 12): array {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(date, '%Y-%m') AS month,
                COUNT(*) as session_count,
                SUM(duration_minutes) as total_duration
            FROM sessions
            WHERE user_id = :user_id 
                AND date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('months', $months, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Delete a session
     */
    public function delete(int $id, int $userId): bool {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM sessions
                WHERE id = :id AND user_id = :user_id
            ");
            
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            
            if ($stmt->rowCount() > 0) {
                Logger::dataChange('sessions', 'DELETE', $id, $userId);
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return false;
        }
    }

    /**
     * Update a session
     */
    public function update(int $id, int $userId, array $data): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE sessions
                SET date = :date,
                    start_time = :start_time,
                    duration_minutes = :duration_minutes,
                    session_type = :session_type,
                    intensity = :intensity,
                    location = :location,
                    notes = :notes
                WHERE id = :id AND user_id = :user_id
            ");
            
            $stmt->execute([
                'id' => $id,
                'user_id' => $userId,
                'date' => $data['date'],
                'start_time' => $data['start_time'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'session_type' => $data['session_type'],
                'intensity' => $data['intensity'] ?? null,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
            
            if ($stmt->rowCount() > 0) {
                Logger::dataChange('sessions', 'UPDATE', $id, $userId);
                return true;
            }
            
            return true; // No changes but valid
        } catch (\PDOException $e) {
            Logger::exception($e, $userId);
            return false;
        }
    }
}

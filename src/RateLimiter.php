<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/database.php';

class RateLimiter {
    private $db;
    private $maxRequests;
    private $windowSeconds;

    public function __construct($action = 'upload') {
        $this->db = Database::getInstance();
        $this->maxRequests = (int)Config::get('RATE_LIMIT_UPLOADS', 10);
        $this->windowSeconds = (int)Config::get('RATE_LIMIT_WINDOW', 60);
    }

    public function check($userId, $action = 'upload') {
        $windowStart = date('Y-m-d H:i:s', time() - $this->windowSeconds);
        
        // Удаление старых записей
        $this->db->delete('rate_limits', 'window_start < ?', [$windowStart]);

        // Проверка текущего окна
        $current = $this->db->fetchOne(
            "SELECT * FROM rate_limits WHERE user_id = ? AND action = ? AND window_start >= ?",
            [$userId, $action, $windowStart]
        );

        if (!$current) {
            // Создание новой записи
            $this->db->insert('rate_limits', [
                'user_id' => $userId,
                'action' => $action,
                'request_count' => 1,
                'window_start' => date('Y-m-d H:i:s')
            ]);
            return ['allowed' => true, 'remaining' => $this->maxRequests - 1];
        }

        if ($current['request_count'] >= $this->maxRequests) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => strtotime($current['window_start']) + $this->windowSeconds
            ];
        }

        // Увеличение счетчика
        $this->db->query(
            "UPDATE rate_limits SET request_count = request_count + 1 WHERE id = ?",
            [$current['id']]
        );

        return [
            'allowed' => true,
            'remaining' => $this->maxRequests - $current['request_count'] - 1
        ];
    }
}

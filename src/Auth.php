<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register($username, $email, $password) {
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Użytkownik o tej nazwie lub emailu już istnieje'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $this->db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash
        ]);

        return ['success' => true, 'user_id' => $userId];
    }

    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? OR email = ?",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Nieprawidłowa nazwa użytkownika lub hasło'];
        }

        $sessionId = $this->createSession($user['id']);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar' => $user['avatar']
            ],
            'session_id' => $sessionId
        ];
    }

    private function createSession($userId) {
        $sessionId = bin2hex(random_bytes(32));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $lifetime = (int)Config::get('SESSION_LIFETIME', 3600);
        $expiresAt = date('Y-m-d H:i:s', time() + $lifetime);

        $this->db->insert('sessions', [
            'id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt
        ]);

        $_SESSION['user_id'] = $userId;
        $_SESSION['session_id'] = $sessionId;
        
        return $sessionId;
    }

    public function logout() {
        if (isset($_SESSION['session_id'])) {
            $this->db->delete('sessions', 'id = ?', [$_SESSION['session_id']]);
        }
        unset($_SESSION['cached_user'], $_SESSION['cached_user_time']);
        session_destroy();
        return ['success' => true];
    }

    public function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $now = time();
        $cacheTtl = 300; // 5 minutes

        // Return cached user if still valid
        if (isset($_SESSION['cached_user']) && isset($_SESSION['cached_user_time']) && ($now - $_SESSION['cached_user_time']) < $cacheTtl) {
            return $_SESSION['cached_user'];
        }

        $session = $this->db->fetchOne(
            "SELECT * FROM sessions WHERE id = ? AND expires_at > NOW()",
            [$_SESSION['session_id'] ?? '']
        );

        if (!$session) {
            session_destroy();
            return null;
        }

        $this->db->query(
            "UPDATE sessions SET last_activity = NOW() WHERE id = ?",
            [$session['id']]
        );

        $user = $this->db->fetchOne(
            "SELECT id, username, email, avatar FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );

        if ($user) {
            $_SESSION['cached_user'] = $user;
            $_SESSION['cached_user_time'] = $now;
        }

        return $user;
    }

    public function requireAuth() {
        $user = $this->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Nieautoryzowany dostęp']);
            exit;
        }
        return $user;
    }

    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

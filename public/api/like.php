<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda nie dozwolona']);
    exit;
}

$auth = new Auth();
$user = $auth->requireAuth();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image_id']) || !isset($data['is_like'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Brak wymaganych pól']);
    exit;
}

$imageId = (int)$data['image_id'];
$isLike = (int)$data['is_like'];

if (!in_array($isLike, [0, 1])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowa wartość']);
    exit;
}

$db = Database::getInstance();

// Проверка существования изображения
$image = $db->fetchOne("SELECT id FROM images WHERE id = ?", [$imageId]);
if (!$image) {
    http_response_code(404);
    echo json_encode(['error' => 'Zdjęcie nie znalezione']);
    exit;
}

// Проверка существующего голоса
$existingLike = $db->fetchOne(
    "SELECT * FROM likes WHERE user_id = ? AND image_id = ?",
    [$user['id'], $imageId]
);

try {
    if ($existingLike) {
        if ($existingLike['is_like'] == $isLike) {
            // Удаление голоса если повторный
            $db->delete('likes', 'id = ?', [$existingLike['id']]);
            
            // Обновление счетчиков
            if ($isLike == 1) {
                $db->query("UPDATE images SET likes_count = likes_count - 1 WHERE id = ?", [$imageId]);
            } else {
                $db->query("UPDATE images SET dislikes_count = dislikes_count - 1 WHERE id = ?", [$imageId]);
            }
            
            echo json_encode(['message' => 'Głos usunięty', 'action' => 'removed']);
        } else {
            // Изменение голоса
            $db->update('likes', ['is_like' => $isLike], 'id = ?', [$existingLike['id']]);
            
            // Обновление счетчиков
            if ($isLike == 1) {
                $db->query("UPDATE images SET likes_count = likes_count + 1, dislikes_count = dislikes_count - 1 WHERE id = ?", [$imageId]);
            } else {
                $db->query("UPDATE images SET likes_count = likes_count - 1, dislikes_count = dislikes_count + 1 WHERE id = ?", [$imageId]);
            }
            
            echo json_encode(['message' => 'Głos zmieniony', 'action' => 'changed']);
        }
    } else {
        // Новый голос
        $db->insert('likes', [
            'user_id' => $user['id'],
            'image_id' => $imageId,
            'is_like' => $isLike
        ]);
        
        // Обновление счетчиков
        if ($isLike == 1) {
            $db->query("UPDATE images SET likes_count = likes_count + 1 WHERE id = ?", [$imageId]);
        } else {
            $db->query("UPDATE images SET dislikes_count = dislikes_count + 1 WHERE id = ?", [$imageId]);
        }
        
        echo json_encode(['message' => 'Głos dodany', 'action' => 'added']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}

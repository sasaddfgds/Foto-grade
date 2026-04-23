<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/database.php';

class ImageHandler {
    private $db;
    private $uploadDir;
    private $maxWidth;
    private $maxHeight;
    private $maxFileSize;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../' . Config::get('UPLOAD_DIR', 'uploads/');
        $this->maxWidth = (int)Config::get('MAX_IMAGE_WIDTH', 1920);
        $this->maxHeight = (int)Config::get('MAX_IMAGE_HEIGHT', 1080);
        $this->maxFileSize = (int)Config::get('MAX_UPLOAD_SIZE', 5242880);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function upload($file, $userId) {
        error_log("ImageHandler::upload started for user: $userId");

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            error_log("Invalid file check failed");
            return ['success' => false, 'message' => 'Nieprawidłowy plik'];
        }

        error_log("File size: " . $file['size']);

        if ($file['size'] > $this->maxFileSize) {
            error_log("File too large");
            return ['success' => false, 'message' => 'Plik jest za duży. Maksymalny rozmiar to 5MB'];
        }

        error_log("Checking MIME type");
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        error_log("MIME type: $mimeType");

        if (!in_array($mimeType, $allowedTypes)) {
            error_log("Invalid MIME type");
            return ['success' => false, 'message' => 'Nieprawidłowy format pliku. Dozwolone: JPEG, PNG, GIF, WebP'];
        }

        error_log("Getting image info");
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            error_log("Failed to get image info");
            return ['success' => false, 'message' => 'Nie można odczytać obrazu'];
        }

        // Skip image optimization for faster upload - just save original file
        error_log("Saving original file (skipping optimization for speed)");
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $extension;
        $filepath = $this->uploadDir . $filename;

        error_log("Moving file to: $filepath");

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("File move failed");
            return ['success' => false, 'message' => 'Błąd podczas zapisywania pliku'];
        }

        error_log("File saved successfully");

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $fileSize = filesize($filepath);

        error_log("Inserting into database: $width x $height, size: $fileSize");

        $imageId = $this->db->insert('images', [
            'user_id' => $userId,
            'filename' => $filename,
            'original_filename' => basename($file['name']),
            'file_path' => '../uploads/' . $filename,
            'width' => $width,
            'height' => $height,
            'file_size' => $fileSize
        ]);

        error_log("Database insert completed, image ID: $imageId");

        return [
            'success' => true,
            'image_id' => $imageId,
            'filename' => $filename,
            'file_path' => 'uploads/' . $filename,
            'width' => $width,
            'height' => $height
        ];
    }

    private function optimizeImage($filepath, $imageInfo) {
        $image = null;
        
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($filepath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($filepath);
                break;
            default:
                return null;
        }

        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($imageInfo[2] == IMAGETYPE_PNG) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        return $image;
    }

    public function getPopularImages($limit = 20) {
        $sql = "
            SELECT i.*, u.username, u.avatar,
                   i.likes_count as likes,
                   i.dislikes_count as dislikes
            FROM images i
            JOIN users u ON i.user_id = u.id
            ORDER BY i.likes_count DESC, i.created_at DESC
            LIMIT ?
        ";
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function getUserImages($userId, $currentUserId = null) {
        $sql = "
            SELECT i.*, u.username, u.avatar,
                   i.likes_count as likes,
                   i.dislikes_count as dislikes
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE i.user_id = ?
            ORDER BY i.created_at DESC
        ";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getImageById($imageId, $currentUserId = null) {
        $sql = "
            SELECT i.*, u.username, u.avatar,
                   i.likes_count as likes,
                   i.dislikes_count as dislikes
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE i.id = ?
        ";
        return $this->db->fetchOne($sql, [$imageId]);
    }

    public function getUserStats($userId) {
        $sql = "
            SELECT 
                COUNT(*) as total_images,
                SUM(likes_count) as total_likes,
                SUM(dislikes_count) as total_dislikes
            FROM images
            WHERE user_id = ?
        ";
        return $this->db->fetchOne($sql, [$userId]);
    }
}

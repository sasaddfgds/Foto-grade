<?php
session_start();
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ImageHandler.php';
require_once __DIR__ . '/../src/Database.php';

$auth = new Auth();
$currentUser = $auth->requireAuth();

$userId = $_GET['id'] ?? $currentUser['id'];
$imageHandler = new ImageHandler();
$userImages = $imageHandler->getUserImages($userId);
$userStats = $imageHandler->getUserStats($userId);

// Получение информации о пользователе профиля
$db = Database::getInstance();
$profileUser = $db->fetchOne(
    "SELECT id, username, avatar FROM users WHERE id = ?",
    [$userId]
);

if (!$profileUser) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl" data-theme="forest">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($profileUser['username']); ?> - Foto-Ocena</title>
    <link rel="stylesheet" href="css/style.css">
    <script>(function(){var t=localStorage.getItem('foto-ocena-theme')||'forest';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
    <header>
        <nav class="navbar" role="navigation" aria-label="Główna nawigacja">
            <div class="nav-container">
                <div class="theme-switcher">
                    <button class="theme-switcher-btn" id="themeSwitcherBtn" aria-label="Zmień motyw" aria-expanded="false">🌲</button>
                    <ul class="theme-dropdown" id="themeDropdown">
                        <li><button data-theme-value="forest"><span class="theme-dot forest"></span>Las</button></li>
                        <li><button data-theme-value="ocean"><span class="theme-dot ocean"></span>Ocean</button></li>
                        <li><button data-theme-value="sunset"><span class="theme-dot sunset"></span>Zachód słońca</button></li>
                    </ul>
                </div>
                <a href="index.php" class="logo">Foto-Ocena</a>
                <ul class="nav-menu">
                    <li><a href="index.php">Główna</a></li>
                    <li><a href="#" id="uploadBtn">Prześlij zdjęcie</a></li>
                    <li class="nav-user-menu">
                        <button class="nav-user-btn" id="navUserBtn" aria-label="Menu użytkownika" aria-expanded="false">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Avatar" class="nav-avatar">
                            <?php else: ?>
                                <div class="nav-avatar-placeholder"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 12 12"><path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                        </button>
                        <ul class="nav-dropdown" id="navDropdown">
                            <li><a href="profile.php?id=<?php echo $currentUser['id']; ?>">Mój profil</a></li>
                            <li><a href="#" id="logoutBtn">Wyloguj</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="profile-header">
            <div class="profile-avatar">
                <?php if ($profileUser['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($profileUser['avatar']); ?>" alt="Avatar <?php echo htmlspecialchars($profileUser['username']); ?>">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo strtoupper(substr($profileUser['username'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($profileUser['username']); ?></h1>
                <div class="profile-stats">
                    <div class="stat">
                        <span class="stat-value"><?php echo $userStats['total_images'] ?? 0; ?></span>
                        <span class="stat-label">Zdjęć</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value"><?php echo $userStats['total_likes'] ?? 0; ?></span>
                        <span class="stat-label">Polubień</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value"><?php echo $userStats['total_dislikes'] ?? 0; ?></span>
                        <span class="stat-label">Niepolubień</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="gallery" aria-label="Galeria zdjęć użytkownika">
            <h2>Zdjęcia użytkownika</h2>
            <div id="imageGrid" class="image-grid">
                <?php if (empty($userImages)): ?>
                    <p class="no-images">Ten użytkownik nie przesłał jeszcze żadnych zdjęć.</p>
                <?php else: ?>
                    <?php foreach ($userImages as $image): ?>
                        <div class="image-card" data-image-id="<?php echo $image['id']; ?>">
                            <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="Zdjęcie od <?php echo htmlspecialchars($image['username']); ?>" loading="lazy">
                            <div class="image-overlay">
                                <div class="image-actions">
                                    <button class="like-btn" data-image-id="<?php echo $image['id']; ?>" aria-label="Polub">
                                        <span class="icon">👍</span>
                                        <span class="count"><?php echo $image['likes']; ?></span>
                                    </button>
                                    <button class="dislike-btn" data-image-id="<?php echo $image['id']; ?>" aria-label="Nie lub">
                                        <span class="icon">👎</span>
                                        <span class="count"><?php echo $image['dislikes']; ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Modal for image upload -->
    <div id="uploadModal" class="modal" role="dialog" aria-labelledby="uploadModalTitle" aria-hidden="true">
        <div class="modal-content">
            <span class="close" aria-label="Zamknij">&times;</span>
            <h2 id="uploadModalTitle">Prześlij zdjęcie</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="imageFile">Wybierz zdjęcie:</label>
                    <input type="file" id="imageFile" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <small>Maksymalny rozmiar: 5MB. Formaty: JPEG, PNG, GIF, WebP</small>
                </div>
                <button type="submit" class="btn btn-primary">Prześlij</button>
            </form>
            <div id="uploadError" class="error-message"></div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../src/Auth.php';
$auth = new Auth();
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pl" data-theme="forest">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foto-Ocena - Oceniaj zdjęcia</title>
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
                    <?php if ($currentUser): ?>
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
                    <?php else: ?>
                        <li><a href="login.php">Zaloguj się</a></li>
                        <li><a href="register.php">Zarejestruj się</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Oceniaj najlepsze zdjęcia</h1>
            <p>Przeglądaj, oceniaj i dziel się swoimi zdjęciami z społecznością</p>
        </section>

        <section class="gallery" aria-label="Galeria zdjęć">
            <h2>Najpopularniejsze zdjęcia</h2>
            <div id="imageGrid" class="image-grid">
                <p class="loading">Ładowanie zdjęć...</p>
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

    <!-- Modal for image view -->
    <div id="imageModal" class="modal" role="dialog" aria-labelledby="imageModalTitle" aria-hidden="true">
        <div class="modal-content modal-large">
            <span class="close" aria-label="Zamknij">&times;</span>
            <div id="imageModalContent"></div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../src/Auth.php';
$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if ($currentUser) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl" data-theme="forest">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - Foto-Ocena</title>
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
                    <li><a href="register.php">Zarejestruj się</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="auth-container">
            <div class="auth-box">
                <h1>Zaloguj się</h1>
                <form id="loginForm">
                    <div class="form-group">
                        <label for="username">Nazwa użytkownika lub email:</label>
                        <input type="text" id="username" name="username" required autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Hasło:</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Zaloguj się</button>
                </form>
                <div id="loginError" class="error-message"></div>
                <p class="auth-link">Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
            </div>
        </section>
    </main>

    <script src="js/auth.js"></script>
</body>
</html>

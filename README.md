# Foto-Ocena

Aplikacja do oceniania zdjęć z możliwością rejestracji, logowania i głosowania.

## Funkcje

- **Rejestracja i logowanie użytkowników** - Bezpieczna autoryzacja z haszowaniem haseł
- **Przesyłanie zdjęć** - Automatyczna optymalizacja do formatu WebP (max 1920x1080)
- **System ocen** - Każdy użytkownik może raz zagłosować (like/dislike) na zdjęcie
- **Galeria popularnych** - Wyświetlanie 20 najpopularniejszych zdjęć
- **Profile użytkowników** - Przeglądanie zdjęć i statystyk użytkowników
- **Aktualizacje w czasie rzeczywistym** - SSE dla natychmiastowych aktualizacji
- **Rate limiting** - Ograniczenie 10 przesłań na minutę dla użytkownika
- **Bezpieczeństwo** - Ochrona XSS, CSRF, bezpieczne nagłówki HTTP

## Wymagania

- PHP 7.4 lub wyższy
- MariaDB 10.3 lub wyższy
- Rozszerzenia PHP: pdo_mysql, gd, fileinfo
- Serwer WWW (Apache/Nginx) lub wbudowany serwer PHP

## Instalacja

### 1. Klonowanie repozytorium

```bash
git clone <repository-url>
cd Foto-Ocena
```

### 2. Konfiguracja bazy danych

Utwórz bazę danych MariaDB i zaimportuj schemat:

```bash
mysql -u root -p < schema.sql
```

Lub użyj phpMyAdmin:
1. Otwórz phpMyAdmin
2. Utwórz nową bazę danych `foto_ocena`
3. Zaimportuj plik `schema.sql`

### 3. Konfiguracja

Edytuj plik `.env` i ustaw dane połączenia z bazą:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=foto_ocena
DB_USER=root
DB_PASS=twoje_haslo
```

### 4. Uprawnienia do zapisu

Upewnij się, że katalog `uploads/` ma uprawnienia do zapisu:

```bash
chmod 755 uploads/
```

Na Windows uprawnienia są zazwyczaj ustawione automatycznie.

### 5. Uruchomienie aplikacji

#### Opcja A: Wbudowany serwer PHP

```bash
php -S localhost:8000 -t public
```

Otwórz przeglądarkę na `http://localhost:8000`

#### Opcja B: Apache/Nginx

Skonfiguruj serwer WWW, aby `public/` był katalogiem głównym dokumentu.

Przykładowa konfiguracja Apache:

```apache
<VirtualHost *:80>
    ServerName foto-ocena.local
    DocumentRoot /sciezka/do/Foto-Ocena/public
    
    <Directory /sciezka/do/Foto-Ocena/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Struktura projektu

```
Foto-Ocena/
├── public/              # Katalog główny serwera WWW
│   ├── index.php       # Strona główna
│   ├── login.php       # Strona logowania
│   ├── register.php    # Strona rejestracji
│   ├── profile.php     # Profil użytkownika
│   ├── api/            # API endpoints
│   ├── css/            # Style CSS
│   └── js/             # Skrypty JavaScript
├── src/                # Klasy PHP
│   ├── Database.php
│   ├── Auth.php
│   ├── ImageHandler.php
│   ├── RateLimiter.php
│   └── Security.php
├── uploads/            # Przesłane zdjęcia
├── config/             # Konfiguracja
├── schema.sql          # Schemat bazy danych
├── .env                # Konfiguracja środowiskowa
└── composer.json       # Zależności (opcjonalne)
```

## API Endpoints

### Autoryzacja
- `POST /api/register.php` - Rejestracja użytkownika
- `POST /api/login.php` - Logowanie użytkownika
- `POST /api/logout.php` - Wylogowanie
- `GET /api/user.php` - Pobierz aktualnego użytkownika

### Zdjęcia
- `POST /api/upload.php` - Prześlij zdjęcie (wymaga autoryzacji)
- `GET /api/images.php?type=popular&limit=20` - Pobierz popularne zdjęcia
- `GET /api/images.php?type=user&user_id={id}` - Pobierz zdjęcia użytkownika
- `GET /api/images.php?type=single&id={id}` - Pobierz pojedyncze zdjęcie

### Głosowanie
- `POST /api/like.php` - Dodaj/usuń like/dislike (wymaga autoryzacji)

### SSE
- `GET /api/events.php` - Server-Sent Events dla aktualizacji w czasie rzeczywistym

## Bezpieczeństwo

Aplikacja implementuje następujące środki bezpieczeństwa:

- **Haszowanie haseł** - Używa `password_hash()` z algorytmem bcrypt
- **XSS Protection** - `htmlspecialchars()` dla wszystkich wyjść
- **CSRF Protection** - Tokeny CSRF dla formularzy
- **SQL Injection Protection** - Prepared statements PDO
- **Security Headers** - CSP, X-Frame-Options, X-XSS-Protection
- **Rate Limiting** - Ograniczenie liczby zapytań
- **Sesje** - Bezpieczne zarządzanie sesjami z automatycznym wygasaniem

## Optymalizacja obrazów

Wszystkie przesłane obrazy są automatycznie:
- Konwertowane do formatu WebP
- Zmniejszane do maksymalnego rozmiaru 1920x1080
- Kompresowane z jakością 85%

## Licencja

MIT License

## Wsparcie

W razie problemów lub pytań, proszę o otwarcie issue na GitHub.

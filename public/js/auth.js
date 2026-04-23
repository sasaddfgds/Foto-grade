// API Client
class ApiClient {
    static async request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Wystąpił błąd');
        }

        return response.json();
    }

    static async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
}

// Auth Handler
class AuthHandler {
    constructor() {
        this.init();
    }

    init() {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        const form = e.target;
        const errorDiv = document.getElementById('loginError');
        const username = form.username.value.trim();
        const password = form.password.value;

        try {
            const result = await ApiClient.post('api/login.php', {
                username: username,
                password: password
            });

            errorDiv.classList.remove('show');
            window.location.href = 'index.php';
        } catch (error) {
            errorDiv.textContent = error.message;
            errorDiv.classList.add('show');
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const form = e.target;
        const errorDiv = document.getElementById('registerError');
        const username = form.username.value.trim();
        const email = form.email.value.trim();
        const password = form.password.value;
        const confirmPassword = form.confirmPassword.value;

        // Client-side validation
        if (password !== confirmPassword) {
            errorDiv.textContent = 'Hasła nie są identyczne';
            errorDiv.classList.add('show');
            return;
        }

        if (password.length < 6) {
            errorDiv.textContent = 'Hasło musi mieć minimum 6 znaków';
            errorDiv.classList.add('show');
            return;
        }

        try {
            const result = await ApiClient.post('api/register.php', {
                username: username,
                email: email,
                password: password
            });

            errorDiv.classList.remove('show');
            alert('Rejestracja zakończona pomyślnie! Możesz się teraz zalogować.');
            window.location.href = 'login.php';
        } catch (error) {
            errorDiv.textContent = error.message;
            errorDiv.classList.add('show');
        }
    }
}

// Theme Manager
const themeIcons = { forest: '🌲', ocean: '🌊', sunset: '🌅' };

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('foto-ocena-theme', theme);
    const btn = document.getElementById('themeSwitcherBtn');
    if (btn) btn.textContent = themeIcons[theme] || '🌲';
    document.querySelectorAll('.theme-dropdown button').forEach(b => {
        b.classList.toggle('active-theme', b.dataset.themeValue === theme);
    });
}

function initTheme() {
    const saved = localStorage.getItem('foto-ocena-theme') || 'forest';
    applyTheme(saved);

    const themeBtn = document.getElementById('themeSwitcherBtn');
    const themeDropdown = document.getElementById('themeDropdown');

    if (themeBtn && themeDropdown) {
        themeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = themeDropdown.classList.toggle('show');
            themeBtn.setAttribute('aria-expanded', isOpen);
        });

        themeDropdown.querySelectorAll('button[data-theme-value]').forEach(b => {
            b.addEventListener('click', (e) => {
                e.stopPropagation();
                applyTheme(b.dataset.themeValue);
                themeDropdown.classList.remove('show');
                themeBtn.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('click', (e) => {
            if (!themeBtn.contains(e.target) && !themeDropdown.contains(e.target)) {
                themeDropdown.classList.remove('show');
                themeBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                themeDropdown.classList.remove('show');
                themeBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    new AuthHandler();
});

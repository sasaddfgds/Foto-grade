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

    static async get(url) {
        return this.request(url, { method: 'GET' });
    }

    static async post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    static async upload(url, formData) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                signal: controller.signal
            });

            clearTimeout(timeoutId);
            const text = await response.text();

            if (!response.ok) {
                try {
                    const error = JSON.parse(text);
                    throw new Error(error.error || 'Wystąpił błąd');
                } catch (e) {
                    throw new Error(text || 'Wystąpił błąd');
                }
            }

            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Nieprawidłowa odpowiedź serwera');
            }
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Przekroczono czas oczekiwania (30 sekund). Spróbuj ponownie.');
            }
            throw error;
        }
    }
}

// Image Gallery
class ImageGallery {
    constructor() {
        this.grid = document.getElementById('imageGrid');
        this.images = [];
        this.userVotes = new Map();
        this.init();
    }

    async init() {
        await this.loadPopularImages();
        this.setupEventListeners();
        this.setupSSE();
    }

    async loadPopularImages() {
        try {
            const data = await ApiClient.get('api/images.php?type=popular&limit=20');
            this.images = data.images;
            this.render();
        } catch (error) {
            this.grid.innerHTML = `<p class="error">Błąd ładowania zdjęć: ${error.message}</p>`;
        }
    }

    render() {
        if (this.images.length === 0) {
            this.grid.innerHTML = '<p class="no-images">Brak zdjęć do wyświetlenia.</p>';
            return;
        }

        this.grid.innerHTML = this.images.map(image => this.createImageCard(image)).join('');
        this.attachImageListeners();
    }

    createImageCard(image) {
        const userVote = this.userVotes.get(image.id);
        const likeClass = userVote === 1 ? 'active' : '';
        const dislikeClass = userVote === 0 ? 'active' : '';

        return `
            <div class="image-card" data-image-id="${image.id}">
                <img src="${image.file_path}" alt="Zdjęcie od ${image.username}" loading="lazy">
                <div class="image-overlay">
                    <div class="user-avatar" onclick="event.stopPropagation(); window.location.href='profile.php?id=${image.user_id}'">
                        ${image.avatar ? 
                            `<img src="${image.avatar}" alt="${image.username}">` : 
                            `<div class="avatar-placeholder">${image.username[0].toUpperCase()}</div>`
                        }
                        <span>${image.username}</span>
                    </div>
                    <div class="image-actions">
                        <button class="like-btn ${likeClass}" data-image-id="${image.id}" aria-label="Polub">
                            <span class="icon">👍</span>
                            <span class="count">${image.likes}</span>
                        </button>
                        <button class="dislike-btn ${dislikeClass}" data-image-id="${image.id}" aria-label="Nie lub">
                            <span class="icon">👎</span>
                            <span class="count">${image.dislikes}</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    attachImageListeners() {
        document.querySelectorAll('.image-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.like-btn') && !e.target.closest('.dislike-btn') && !e.target.closest('.user-avatar')) {
                    const imageId = card.dataset.imageId;
                    this.showImageModal(imageId);
                }
            });
        });

        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleVote(btn.dataset.imageId, 1);
            });
        });

        document.querySelectorAll('.dislike-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.handleVote(btn.dataset.imageId, 0);
            });
        });
    }

    async handleVote(imageId, isLike) {
        try {
            const result = await ApiClient.post('api/like.php', { image_id: imageId, is_like: isLike });
            
            // Update local state
            if (result.action === 'added' || result.action === 'changed') {
                this.userVotes.set(imageId, isLike);
            } else if (result.action === 'removed') {
                this.userVotes.delete(imageId);
            }

            // Refresh the specific image card
            await this.loadPopularImages();
        } catch (error) {
            alert('Błąd podczas głosowania: ' + error.message);
        }
    }

    showImageModal(imageId) {
        const image = this.images.find(img => img.id == imageId);
        if (!image) return;

        const modal = document.getElementById('imageModal');
        const content = document.getElementById('imageModalContent');
        
        content.innerHTML = `
            <img src="${image.file_path}" alt="Zdjęcie od ${image.username}" style="max-width: 100%; border-radius: 8px;">
            <div style="margin-top: 1rem;">
                <h3>Przez: ${image.username}</h3>
                <p>Liki: ${image.likes} | Nie lubią: ${image.dislikes}</p>
            </div>
        `;
        
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }

    setupEventListeners() {
        // Upload modal
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadModal = document.getElementById('uploadModal');
        const closeButtons = document.querySelectorAll('.close');

        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => {
                uploadModal.classList.add('show');
                uploadModal.setAttribute('aria-hidden', 'false');
            });
        }

        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.closeModals();
            });
        });

        // Close modal on outside click
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModals();
            }
        });

        // Upload form
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleUpload(e));
        }

        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }
    }

    closeModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        });
    }

    async handleUpload(e) {
        e.preventDefault();
        const form = e.target;
        const fileInput = document.getElementById('imageFile');
        const errorDiv = document.getElementById('uploadError');

        if (!fileInput.files[0]) {
            errorDiv.textContent = 'Wybierz plik';
            errorDiv.classList.add('show');
            return;
        }

        const formData = new FormData();
        formData.append('image', fileInput.files[0]);

        try {
            console.log('Uploading file...');
            const result = await ApiClient.upload('api/upload.php', formData);
            console.log('Upload result:', result);
            errorDiv.classList.remove('show');
            this.closeModals();
            form.reset();
            await this.loadPopularImages();
            alert('Zdjęcie przesłane pomyślnie!');
        } catch (error) {
            console.error('Upload error:', error);
            errorDiv.textContent = error.message;
            errorDiv.classList.add('show');
        }
    }

    async handleLogout() {
        try {
            await ApiClient.post('api/logout.php', {});
            window.location.href = 'login.php';
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = 'login.php';
        }
    }

    setupSSE() {
        // Disabled SSE to prevent page load blocking on slow connections
        return;
        
        try {
            const eventSource = new EventSource('api/events.php');
            
            eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                
                if (data.type === 'likes_update') {
                    // Refresh images when likes are updated
                    this.loadPopularImages();
                } else if (data.type === 'new_images') {
                    // Refresh when new images are uploaded
                    this.loadPopularImages();
                }
            };

            eventSource.onerror = (error) => {
                console.error('SSE error:', error);
                eventSource.close();
            };
        } catch (error) {
            console.error('SSE not supported:', error);
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

    if (document.getElementById('imageGrid')) {
        new ImageGallery();
    }

    // Nav user dropdown
    const navUserBtn = document.getElementById('navUserBtn');
    const navDropdown = document.getElementById('navDropdown');

    if (navUserBtn && navDropdown) {
        navUserBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = navDropdown.classList.toggle('show');
            navUserBtn.setAttribute('aria-expanded', isOpen);
        });

        document.addEventListener('click', (e) => {
            if (!navUserBtn.contains(e.target) && !navDropdown.contains(e.target)) {
                navDropdown.classList.remove('show');
                navUserBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                navDropdown.classList.remove('show');
                navUserBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});

/**
 * CMS Theme API Client
 * Скрипт для взаимодействия с API из шаблонов
 */

// Основной класс для работы с API
class CmsApi {
    constructor() {
        this.baseUrl = '/api';
    }

    /**
     * Выполняет GET-запрос к API
     */
    async get(endpoint) {
        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'include'
            });

            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Получает список типов контента
     */
    async getContentTypes() {
        return await this.get('/content-types');
    }

    /**
     * Получает список записей определенного типа
     */
    async getContentList(typeId, params = {}) {
        let queryString = '';
        if (Object.keys(params).length > 0) {
            queryString = '?' + new URLSearchParams(params).toString();
        }
        return await this.get(`/content-types/${typeId}/content${queryString}`);
    }

    /**
     * Получает отдельную запись
     */
    async getContentItem(typeId, itemId) {
        return await this.get(`/content-types/${typeId}/content/${itemId}`);
    }

    /**
     * Получает настройки сайта
     */
    async getSettings() {
        return await this.get('/settings');
    }

    /**
     * Получает список доступных тем
     */
    async getThemes() {
        return await this.get('/themes');
    }

    /**
     * Получает информацию о конкретной теме
     */
    async getThemeInfo(themeName) {
        return await this.get(`/themes/${themeName}`);
    }
}

// Инициализация API клиента
const cmsApi = new CmsApi();

// Инициализация функций для шаблонов
document.addEventListener('DOMContentLoaded', function() {
    // Обработка форм поиска
    const searchForms = document.querySelectorAll('.search-form');
    if (searchForms.length > 0) {
        searchForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const searchInput = form.querySelector('input[name="search"]');
                if (!searchInput.value.trim()) {
                    e.preventDefault();
                }
            });
        });
    }

    // Плавная прокрутка для якорных ссылок
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Динамическая загрузка контента через API (пример)
    const dynamicContentContainers = document.querySelectorAll('[data-dynamic-content]');
    if (dynamicContentContainers.length > 0) {
        dynamicContentContainers.forEach(container => {
            const typeId = container.dataset.typeId;
            const limit = container.dataset.limit || 5;
            
            if (typeId) {
                cmsApi.getContentList(typeId, { limit, status: 'published' })
                    .then(response => {
                        if (response.success && response.data) {
                            renderContentItems(container, response.data);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading dynamic content:', error);
                    });
            }
        });
    }
});

/**
 * Отображает элементы контента в указанном контейнере
 */
function renderContentItems(container, items) {
    if (!items || items.length === 0) {
        container.innerHTML = '<p>Нет доступного контента</p>';
        return;
    }

    const list = document.createElement('div');
    list.className = 'row';

    items.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-4';

        const card = document.createElement('div');
        card.className = 'card h-100';

        let imageHtml = '';
        if (item.featured_image) {
            imageHtml = `<img src="${item.featured_image}" class="card-img-top" alt="${item.title}">`;
        }

        const description = item.description ? `<p class="card-text">${item.description.substring(0, 100)}...</p>` : '';

        card.innerHTML = `
            ${imageHtml}
            <div class="card-body">
                <h5 class="card-title">${item.title}</h5>
                ${description}
                <a href="/p/${item.slug}" class="btn btn-primary">Читать далее</a>
            </div>
            <div class="card-footer text-muted">
                ${new Date(item.created_at).toLocaleDateString()}
            </div>
        `;

        col.appendChild(card);
        list.appendChild(col);
    });

    container.innerHTML = '';
    container.appendChild(list);
} 
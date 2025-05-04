import axios, { AxiosRequestConfig } from 'axios';

// Определяем базовый URL в зависимости от среды
// В development используем proxy через Vite, в production - прямой URL
const BASE_URL = import.meta.env.DEV 
  ? '/api' // Будет проксироваться через настройки Vite
  : 'http://localhost/cms/backend/api/';

// Базовая конфигурация axios
const apiClient = axios.create({
  baseURL: BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  // Включаем передачу учетных данных (cookies, authorization headers)
  withCredentials: true,
});

// Добавляем обработчик для запросов
apiClient.interceptors.request.use(
  async (config) => {
    try {
      // Добавляем токен авторизации если он есть
      const token = localStorage.getItem('token');
      
      if (token && config.headers) {
        // Добавляем токен в заголовок
        config.headers['Authorization'] = `Bearer ${token}`;
        
        // Логируем для отладки с маскировкой содержимого токена
        console.log(`Устанавливаем заголовок авторизации: Bearer ${token.substring(0, 15)}...`);
      }
      
      // Добавляем случайный параметр для предотвращения кэширования GET-запросов
      if (config.method?.toLowerCase() === 'get') {
        config.params = {
          ...config.params,
          _nocache: new Date().getTime()
        };
      }
      
      // Устанавливаем таймаут запроса
      config.timeout = 10000; // 10 секунд
      
      // Для отладки
      console.log(`Отправка ${config.method?.toUpperCase()} запроса к ${config.url}`, 
                config.data ? { data: config.data } : '');
    } catch (error) {
      console.error('Ошибка при подготовке запроса:', error);
    }
                
    return config;
  },
  (error) => {
    console.error('Ошибка запроса:', error);
    return Promise.reject(error);
  }
);

// Интерцептор для обработки ответов
apiClient.interceptors.response.use(
  (response) => {
    // Для отладки
    console.log(`Ответ от ${response.config.url}:`, response.data);
    return response;
  },
  (error) => {
    if (error.response) {
      console.error(`Ошибка ${error.response.status} от ${error.config.url}:`, 
                  error.response.data || error.message);
      
      // Обработка истечения срока действия токена
      if (error.response.status === 401) {
        // Если это был запрос проверки текущего пользователя, то не выполняем автоматический выход
        if (error.config.url === '/me') {
          console.log('Получен 401 от /me - обработка в AuthContext');
        } else {
          // Для других запросов выполняем выход из системы
          console.log('Получен 401 от API - автоматический выход из системы');
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          
          // Выполняем перенаправление с небольшой задержкой,
          // чтобы дать время завершиться текущему запросу
          setTimeout(() => {
            window.location.href = '/login';
          }, 100);
        }
      } else if (error.response.status >= 500) {
        console.error('Серверная ошибка:', error.response.data);
      }
    } else if (error.request) {
      // Запрос был выполнен, но не получен ответ
      console.error('Нет ответа от сервера:', error.request);
      
      // Проверяем, не истек ли таймаут
      if (error.code === 'ECONNABORTED') {
        console.error('Превышено время ожидания запроса');
      }
    } else {
      // Произошла ошибка при настройке запроса
      console.error('Ошибка при настройке запроса:', error.message);
    }
    
    return Promise.reject(error);
  }
);

// Интерфейсы для типизации
export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  status: string;
  last_login?: string;
  created_at: string;
  updated_at: string;
}

export interface LoginData {
  email: string;
  password: string;
}

export interface LoginResponse {
  user: User;
  token: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
}

// API методы для работы с аутентификацией
export const authAPI = {
  login: (data: LoginData) => {
    console.log('Login attempt with:', data);
    
    // Дополнительное логирование для отладки
    if (data.email === 'admin@example.com' && data.password === 'admin123') {
      console.log('Using test admin account...');
    }
    
    return apiClient.post<LoginResponse>('/login', data);
  },
  register: (data: RegisterData) => apiClient.post<LoginResponse>('/register', data),
  me: () => apiClient.get<User>('/me'),
};

// API методы для работы с пользователями
export const usersAPI = {
  getAll: (page = 1, limit = 20, filters = {}) => 
    apiClient.get('/users', { params: { page, limit, ...filters } }),
  getById: (id: number) => apiClient.get(`/users/${id}`),
  create: (data: any) => apiClient.post('/users', data),
  update: (id: number, data: any) => apiClient.put(`/users/${id}`, data),
  delete: (id: number) => apiClient.delete(`/users/${id}`),
};

// API методы для страниц
export const pagesAPI = {
  getAll: (page = 1, limit = 20, filters = {}) => 
    apiClient.get('/pages', { params: { page, limit, ...filters } }),
  getById: (id: number) => apiClient.get(`/pages/${id}`),
  create: (data: any) => apiClient.post('/pages', data),
  update: (id: number, data: any) => apiClient.put(`/pages/${id}`, data),
  delete: (id: number) => apiClient.delete(`/pages/${id}`),
};

// API методы для типов контента
export const contentTypesAPI = {
  getAll: () => apiClient.get('/content-types'),
  getById: (id: number) => apiClient.get(`/content-types/${id}`),
  create: (data: any) => apiClient.post('/content-types', data),
  update: (id: number, data: any) => apiClient.put(`/content-types/${id}`, data),
  delete: (id: number) => apiClient.delete(`/content-types/${id}`),
  
  // Методы для работы с полями типов контента
  getContentTypeFields: (contentTypeId: number) => 
    apiClient.get(`/content-types/${contentTypeId}/fields`),
  getContentTypeField: (fieldId: number) => 
    apiClient.get(`/content-types/fields/${fieldId}`),
  createContentTypeField: (contentTypeId: number, data: any) => 
    apiClient.post(`/content-types/${contentTypeId}/fields`, data),
  updateContentTypeField: (fieldId: number, data: any) => 
    apiClient.put(`/content-types/fields/${fieldId}`, data),
  deleteContentTypeField: (fieldId: number) => {
    console.log('Удаление поля с ID:', fieldId);
    return apiClient.delete(`/content-types/fields/${fieldId}`);
  }
};

// API методы для контента
export const contentAPI = {
  getAll: (typeId: number, page = 1, limit = 20, filters = {}) => 
    apiClient.get(`/content-types/${typeId}/content`, { params: { page, limit, ...filters } }),
  getById: (typeId: number, id: number, params = {}) => 
    apiClient.get(`/content-types/${typeId}/content/${id}`, { params }),
  create: (typeId: number, data: any) => {
    // Убедимся, что content_type_id всегда добавлен в данные как число
    const contentTypeIdNum = Number(typeId);
    
    console.log('API create - преобразование typeId:', { 
      original: typeId, 
      type: typeof typeId, 
      converted: contentTypeIdNum, 
      typeAfter: typeof contentTypeIdNum,
      isNaN: isNaN(contentTypeIdNum)
    });
    
    if (isNaN(contentTypeIdNum)) {
      console.error('Некорректный ID типа контента:', typeId);
      return Promise.reject(new Error(`Некорректный ID типа контента: ${typeId}`));
    }
    
    const contentData = {
      ...data,
      content_type_id: contentTypeIdNum  // Явно как число
    };
    
    console.log(`Создание контента для типа ${contentTypeIdNum}:`, {
      url: `/content-types/${contentTypeIdNum}/content`,
      contentTypeId: contentTypeIdNum,
      contentTypeIdType: typeof contentTypeIdNum,
      data: contentData
    });
    
    // Добавляем typeId в URL и в данные для гарантии
    return apiClient.post(`/content-types/${contentTypeIdNum}/content`, contentData);
  },
  update: (typeId: number, id: number, data: any) => {
    // Принудительное преобразование в числа
  
    const contentTypeIdNum = parseInt(String(typeId), 10);
    const contentIdNum = parseInt(String(id), 10);
    
    // console.log('API update - параметры запроса:', { 
    //   typeId: contentTypeIdNum,
    //   id: contentIdNum,
    //   data
    // });
    
    // Проверка валидности ID
    if (isNaN(contentTypeIdNum) || contentTypeIdNum <= 0) {
      console.error('Некорректный ID типа контента:', typeId);
      return Promise.reject(new Error(`Некорректный ID типа контента: ${typeId}`));
    }
    
    if (isNaN(contentIdNum) || contentIdNum <= 0) {
      console.error('Некорректный ID записи:', id);
      return Promise.reject(new Error(`Некорректный ID записи: ${id}`));
    }
    
    // Создаем копию данных для обновления
    const updateData = { ...data };
    
    // Добавляем гарантированные изменения, чтобы обновление точно сработало
    updateData.updated_at = new Date().toISOString().slice(0, 19).replace('T', ' ');
    
    // Всегда добавляем content_type_id в данные
    updateData.content_type_id = contentTypeIdNum;
    
    // Если есть title, добавляем метку времени
    if (updateData.title) {
      // Удаляем любую предыдущую временную метку
      const baseTitle = updateData.title.replace(/\s+\(\d{2}:\d{2}:\d{2}\)$/, '');
      const now = new Date();
      const timeStr = [
        now.getHours().toString().padStart(2, '0'),
        now.getMinutes().toString().padStart(2, '0'),
        now.getSeconds().toString().padStart(2, '0')
      ].join(':');
      updateData.title = `${baseTitle} (${timeStr})`;
    }

    return apiClient.put(`/content-types/${contentTypeIdNum}/content/${contentIdNum}`, updateData);
  },
  delete: (typeId: number, id: number) => {
    console.log('Удаление записи с ID:', id, 'для типа:', typeId);
    return apiClient.delete(`/content-types/${typeId}/content/${id}`);
  }
};

// API методы для медиа-файлов
export const mediaAPI = {
  getAll: (page = 1, limit = 20, filters = {}) => 
    apiClient.get('/media', { params: { page, limit, ...filters } }),
  getById: (id: number) => apiClient.get(`/media/${id}`),
  upload: (file: File, data = {}) => {
    const formData = new FormData();
    formData.append('file', file);
    
    Object.entries(data).forEach(([key, value]) => {
      formData.append(key, value as string);
    });
    
    return apiClient.post('/media', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  },
  delete: (id: number) => apiClient.delete(`/media/${id}`),
};

// API методы для работы с темами
export const themesAPI = {
  getAll: () => 
    apiClient.get('/themes'),
  
  getById: (id: string) => 
    apiClient.get(`/themes/${id}`),
  
  activate: (themeName: string) => 
    apiClient.post('/themes/activate', { theme_name: themeName }),
  
  preview: (themeName: string) => 
    apiClient.get(`/preview/${themeName}`),
};

// API методы для работы с модулями
export const modulesAPI = {
  getAll: () => apiClient.get('/modules'),
  getById: (id: number) => apiClient.get(`/modules/${id}`),
  activate: (slug: string) => {
    console.log(`Активация модуля ${slug}...`);
    return apiClient.post(`/modules/${slug}/activate`)
      .then(response => {
        console.log(`Ответ от /modules/${slug}/activate:`, response);
        return response;
      })
      .catch(error => {
        console.error(`Ошибка при активации модуля ${slug}:`, error);
        throw error;
      });
  },
  deactivate: (slug: string) => apiClient.post(`/modules/${slug}/deactivate`),
  install: (slug: string) => apiClient.post(`/modules/${slug}/install`),
  uninstall: (slug: string) => apiClient.post(`/modules/${slug}/uninstall`),
  getModulesStatus: () => {
    console.log('Запрос статуса модулей...');
    return apiClient.get('/modules/status').then(response => {
      console.log('Ответ от /modules/status:', response);
      return response;
    }).catch(error => {
      console.error('Ошибка при получении статуса модулей:', error);
      throw error;
    });
  },
};

// API методы для работы с товарами и категориями магазина
export const shopAPI = {
  getProducts: (params = {}) => {
    console.log('Отправка запроса на получение списка товаров с параметрами:', params);
    return apiClient.get('/shop/products', { params });
  },
  
  getProduct: (id: number) => {
    console.log('Отправка запроса на получение товара с ID:', id);
    const url = `/shop/products/${id}`;
    console.log('URL запроса:', url);
    return apiClient.get(url);
  },
  
  createProduct: (data: any) => {
    // Используем CORS proxy для обхода проблем с CORS
    console.log('Отправка запроса на создание товара с данными:', data);

    // Определяем URL CORS proxy
    const proxyUrl = 'http://localhost/cms/backend/api/cors_proxy.php/shop/products';
    console.log('Используем CORS proxy:', proxyUrl);
    
    // Отправляем запрос с использованием прямого вызова axios
    return axios({
      method: 'post',
      url: proxyUrl,
      data: data,
      headers: {
        'Content-Type': 'application/json'
      }
    }).catch(error => {
      console.error('Ошибка при создании товара:', error);
      if (error.response) {
        console.error('Статус ответа:', error.response.status);
        console.error('Данные ответа:', error.response.data);
      }
      throw error;
    });
  },
  
  updateProduct: (id: number, data: any) => {
    console.log('Отправка PUT запроса на обновление товара с ID:', id);
    console.log('Данные обновления:', data);
    const url = `/shop/products/${id}`;
    console.log('URL запроса:', url);
    console.log('Полный URL с BASE_URL:', BASE_URL + url);
    return apiClient.put(url, data);
  },
  
  deleteProduct: (id: number) => {
    console.log('Отправка запроса на удаление товара с ID:', id);
    const url = `/shop/products/${id}`;
    console.log('URL запроса:', url);
    return apiClient.delete(url);
  },
  
  getCategories: () => {
    return apiClient.get('/shop/categories');
  },
  
  getCategory: (id: number) => {
    return apiClient.get(`/shop/categories/${id}`);
  },
  
  createCategory: (data: any) => 
    apiClient.post('/shop/categories', data),
  updateCategory: (id: number, data: any) => 
    apiClient.put(`/shop/categories/${id}`, data),
  deleteCategory: (id: number) => 
    apiClient.delete(`/shop/categories/${id}`),
};

// Общий экспорт API клиента
export default apiClient; 
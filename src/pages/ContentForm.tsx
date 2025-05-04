import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, data } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { contentAPI, contentTypesAPI } from '../services/api';

interface Field {
  id: number;
  name: string;
  label: string;
  field_type: string;
  required: boolean;
  default_value?: string | null;
  settings?: any;
}

interface ContentType {
  id: number;
  name: string;
  label: string;
  slug: string;
  fields: Field[];
}

const ContentForm: React.FC = () => {
  const { typeId, id } = useParams<{ typeId: string; id: string }>();
  const navigate = useNavigate();
  const isEditing = !!id;

  const [contentType, setContentType] = useState<ContentType | null>(null);
  const [formData, setFormData] = useState<Record<string, any>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  // Загружаем тип контента и его поля
  useEffect(() => {
    const fetchContentType = async () => {
      if (!typeId) return;
      
      try {
        console.log('Загрузка типа контента с ID:', typeId);
        const response = await contentTypesAPI.getById(parseInt(typeId));
        console.log('Ответ от API:', response.data);
        
        if (response.data.success) {
          setContentType(response.data.data);
          
          // Инициализируем формы значениями по умолчанию для полей
          const initialData: Record<string, any> = {};
          if (response.data.data.fields) {
            response.data.data.fields.forEach((field: Field) => {
              initialData[field.name] = field.default_value || '';
            });
          }
          
          // Добавляем поле для статуса
          initialData.status = 'draft';
          
          setFormData(initialData);
        } else {
          setError(response.data.message || 'Ошибка при загрузке типа контента');
        }
      } catch (err) {
        console.error('Ошибка при загрузке типа контента:', err);
        setError('Не удалось загрузить информацию о типе контента');
      }
    };
    
    fetchContentType();
  }, [typeId]);
  
  // Если редактируем, загружаем данные записи
  useEffect(() => {
    const fetchContent = async () => {
      if (!typeId || !id || !isEditing) return;
      
      try {
        const response = await contentAPI.getById(parseInt(typeId), parseInt(id));
        
        if (response.data.success) {
          // Объединяем существующие значения полей с загруженными данными
          setFormData(prev => ({
            ...prev,
            ...response.data.data,
            // Если есть поля с кастомными значениями, их тоже добавляем
            ...(response.data.data.fields ? 
              Object.keys(response.data.data.fields).reduce((acc, key) => {
                acc[key] = response.data.data.fields[key].value;
                return acc;
              }, {} as Record<string, any>) : {})
          }));
        } else {
          setError(response.data.message || 'Ошибка при загрузке записи');
        }
      } catch (err) {
        console.error('Ошибка при загрузке записи:', err);
        setError('Не удалось загрузить запись');
      } finally {
        setLoading(false);
      }
    };
    
    if (isEditing) {
      fetchContent();
    } else {
      setLoading(false);
    }
  }, [typeId, id, isEditing]);
  
  const handleInputChange = (name: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (saving) return;
    setSaving(true);
    setError(null);
    
    if (!contentType) {
      setError('Тип контента не загружен');
      setSaving(false);
      return;
    }
    
    // Удостоверимся, что у нас есть корректный ID типа контента
    const contentTypeIdNum = parseInt(typeId || '0');
    
    // Подробное логирование для отладки
    console.log('Преобразование ID типа контента:', {
      original: typeId,
      trimmed: typeId ? typeId.trim() : '',
      asNumber: contentTypeIdNum,
      isNaN: isNaN(contentTypeIdNum)
    });
    
    if (isNaN(contentTypeIdNum)) {
      setError('Некорректный ID типа контента');
      setSaving(false);
      return;
    }
    
    // Если редактируем, проверяем ID записи
    let contentIdNum: number | null = null;
    if (isEditing && id) {
      contentIdNum = parseInt(id);
      console.log('Преобразование ID записи:', {
        original: id,
        trimmed: id ? id.trim() : '',
        asNumber: contentIdNum,
        isNaN: isNaN(contentIdNum)
      });
      
      if (isNaN(contentIdNum)) {
        setError('Некорректный ID записи');
        setSaving(false);
        return;
      }
    }
    
    // Валидация обязательных полей
    const requiredFields = contentType.fields.filter(field => field.required);
    const missingFields = requiredFields.filter(field => 
      !formData[field.name] && formData[field.name] !== 0 && formData[field.name] !== false
    );
    
    if (missingFields.length > 0) {
      setError(`Пожалуйста, заполните обязательные поля: ${missingFields.map(f => f.label).join(', ')}`);
      setSaving(false);
      return;
    }
    
    try {
      let response;
      
      // Подготавливаем данные для отправки
      const contentData: Record<string, any> = {
        ...formData,
        // Явно добавляем ID типа контента как число
        content_type_id: contentTypeIdNum,
        // Если редактируем, явно добавляем ID записи
        ...(isEditing && contentIdNum ? { id: contentIdNum } : {}),
        // Если не указан title, используем временное значение
        title: formData.title || `Новая запись ${new Date().toLocaleString()}`,
      };
      
      // Генерируем slug из заголовка, если его нет
      if (!contentData.slug && contentData.title) {
        contentData.slug = contentData.title
          .toLowerCase()
          .replace(/[^\wа-яё\s-]/g, '')
          .replace(/[\s_-]+/g, '-')
          .replace(/^-+|-+$/g, '');
      }
      
      // Добавляем автора, если он не указан
      if (!contentData.author_id) {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        contentData.author_id = user.id || 1; // Используем ID из localStorage или по умолчанию 1
      }
      
      // Гарантируем, что даже пустые поля отправляются в контролер
      contentType.fields.forEach(field => {
        if (contentData[field.name] === undefined) {
          contentData[field.name] = '';
        }
      });
      
      // Подробное логирование для отладки
      console.log(`${isEditing ? 'Обновление' : 'Создание'} контента для типа ${contentTypeIdNum}:`, {
        url: isEditing 
          ? `/content-types/${contentTypeIdNum}/content/${contentIdNum}` 
          : `/content-types/${contentTypeIdNum}/content`,
        method: isEditing ? 'PUT' : 'POST',
        data: contentData
      });
      
      try {
        if (isEditing && contentIdNum !== null) {
          response = await contentAPI.update(contentTypeIdNum, contentIdNum, contentData);
        } else {
          // Добавляем несколько повторных попыток на случай проблем с соединением
          let attempts = 0;
          const maxAttempts = 2;
          
          while (attempts <= maxAttempts) {
            try {
              console.log(`Попытка создания контента ${attempts + 1}/${maxAttempts + 1}`);
              response = await contentAPI.create(contentTypeIdNum, contentData);
              break; // Если успешно, выходим из цикла
            } catch (err) {
              attempts++;
              console.error(`Ошибка попытки ${attempts}:`, err);
              
              if (attempts > maxAttempts) {
                throw err; // Если все попытки исчерпаны, пробрасываем ошибку
              }
              
              // Небольшая задержка перед следующей попыткой
              await new Promise(resolve => setTimeout(resolve, 500));
            }
          }
        }
        
        console.log('Ответ от API:', response?.data);
        
        if (response?.data?.success) {
          // Добавляем небольшую задержку и показываем сообщение об успехе
          setSaving(false);
          setError(null);
          
          // Создаем уведомление об успешном сохранении
          const successMsg = document.createElement('div');
          successMsg.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow z-50';
          successMsg.textContent = `${isEditing ? 'Запись успешно обновлена' : 'Запись успешно создана'}`;
          document.body.appendChild(successMsg);
          
          // Если мы редактируем запись, обновляем данные формы перед перенаправлением
          if (isEditing && contentIdNum) {
            try {
              // Добавляем случайный параметр для избегания кэширования
              const freshDataResponse = await contentAPI.getById(
                contentTypeIdNum, 
                contentIdNum, 
                { _nocache: Date.now() }
              );
              
              if (freshDataResponse?.data?.success) {
                console.log('Обновленные данные получены:', freshDataResponse.data);
                
                // Обновляем данные формы, чтобы пользователь мог видеть изменения
                const freshData = freshDataResponse.data.data;
                setFormData(prev => ({
                  ...prev,
                  ...freshData,
                  // Если есть поля с кастомными значениями, их тоже обновляем
                  ...(freshData.fields ? 
                    Object.keys(freshData.fields).reduce((acc, key) => {
                      acc[key] = freshData.fields[key].value;
                      return acc;
                    }, {} as Record<string, any>) : {})
                }));
              }
            } catch (refreshErr) {
              console.error('Ошибка при получении обновленных данных:', refreshErr);
            }
          }
          
          // Удаляем сообщение и выполняем перенаправление после увеличенной задержки
          // setTimeout(() => {
          //   successMsg.remove();
          //   // Возвращаемся к списку записей с параметром для принудительного обновления
          //   navigate(`/content-types/${typeId}/content?refresh=${Date.now()}`);
          // }, 500);
        } else {
          setError(response?.data?.message || 'Ошибка при сохранении записи');
        }
      } catch (err) {
        console.error('Ошибка запроса API:', err);
        throw err; // Пробрасываем ошибку для внешнего обработчика
      }
    } catch (err: any) {
      console.error('Ошибка при сохранении записи:', err);
      // Показываем более подробную информацию об ошибке
      const errorMessage = err.response?.data?.message || err.message || 'Не удалось сохранить запись';
      setError(`Ошибка: ${errorMessage}`);
    } finally {
      setSaving(false);
    }
  };
  
  // Функция для отрисовки поля в зависимости от его типа
  const renderField = (field: Field) => {
    const value = formData[field.name] !== undefined ? formData[field.name] : '';
    
    switch (field.field_type) {
      case 'text':
        return (
          <input
            type="text"
            id={`field-${field.name}`}
            value={value}
            onChange={(e) => handleInputChange(field.name, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            required={field.required}
          />
        );
        
      case 'textarea':
        return (
          <textarea
            id={`field-${field.name}`}
            value={value}
            onChange={(e) => handleInputChange(field.name, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            rows={4}
            required={field.required}
          />
        );
        
      case 'number':
        return (
          <input
            type="number"
            id={`field-${field.name}`}
            value={value}
            onChange={(e) => handleInputChange(field.name, e.target.value ? parseFloat(e.target.value) : '')}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            required={field.required}
          />
        );
        
      case 'checkbox':
        return (
          <input
            type="checkbox"
            id={`field-${field.name}`}
            checked={!!value}
            onChange={(e) => handleInputChange(field.name, e.target.checked)}
            className="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary"
          />
        );
        
      case 'select':
        const options = field.settings?.options || [];
        return (
          <select
            id={`field-${field.name}`}
            value={value}
            onChange={(e) => handleInputChange(field.name, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            required={field.required}
          >
            <option value="">-- Выберите --</option>
            {options.map((option: { value: string; label: string }) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        );
        
      case 'date':
        return (
          <input
            type="date"
            id={`field-${field.name}`}
            value={value}
            onChange={(e) => handleInputChange(field.name, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            required={field.required}
          />
        );
        
      case 'image':
        return (
          <div>
            <input
              type="text"
              id={`field-${field.name}`}
              value={value}
              onChange={(e) => handleInputChange(field.name, e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="URL изображения"
              required={field.required}
            />
            {value && (
              <div className="mt-2">
                <img 
                  src={value} 
                  alt="Preview" 
                  className="w-24 h-24 object-cover rounded"
                />
              </div>
            )}
          </div>
        );
        
      default:
        return (
          <input
            type="text"
            id={`field-${field.name}`}
            value={value}
            onChange={(e) => handleInputChange(field.name, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            required={field.required}
          />
        );
    }
  };
  
  // Проверяем наличие стандартных полей
  const hasStandardFields = (fields: Field[]) => {
    const standardFieldNames = ['title', 'description', 'image'];
    return standardFieldNames.every(name => fields.some(field => field.name === name));
  };
  
  if (loading) {
    return (
      <div className="flex items-center justify-center h-full">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
          <p className="mt-4 text-gray-500">Загрузка данных...</p>
        </div>
      </div>
    );
  }
  
  if (error) {
    return (
      <div className="p-6 bg-red-50 rounded-lg border border-red-200">
        <h2 className="text-xl font-bold text-red-700 mb-2">Ошибка</h2>
        <p className="text-red-600">{error}</p>
        <button
          onClick={() => navigate(-1)}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
        >
          Вернуться назад
        </button>
      </div>
    );
  }
  
  if (!contentType) {
    return (
      <div className="p-6 bg-red-50 rounded-lg border border-red-200">
        <h2 className="text-xl font-bold text-red-700 mb-2">Ошибка</h2>
        <p className="text-red-600">Тип контента не найден</p>
        <button
          onClick={() => navigate('/content-types')}
          className="mt-4 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
        >
          К списку типов контента
        </button>
      </div>
    );
  }
  
  return (
    <div className="container mx-auto px-4 py-6">
      <div className="mb-6">
        <button
          onClick={() => navigate(-1)}
          className="flex items-center text-gray-600 hover:text-gray-900 mb-2"
        >
          <ArrowLeft className="w-4 h-4 mr-1" />
          Назад
        </button>
        <h1 className="text-2xl font-bold">
          {isEditing ? `Редактирование записи - ${contentType.label}` : `Новая запись - ${contentType.label}`}
        </h1>
        <p className="text-sm text-gray-500">ID типа контента: {contentType.id}, Slug: {contentType.slug}</p>
      </div>
      
      <div className="bg-white rounded-lg shadow p-6">
        <form onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 gap-6">
            {/* Базовые поля */}
            <div className="mb-4">
              <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">
                Статус
              </label>
              <select
                id="status"
                value={formData.status || 'draft'}
                onChange={(e) => handleInputChange('status', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="draft">Черновик</option>
                <option value="published">Опубликовано</option>
                <option value="archived">Архив</option>
              </select>
            </div>

            {/* Стандартные поля (title, description, image) */}
            <div className="mb-4">
              <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-1">
                Заголовок <span className="text-red-600">*</span>
              </label>
              <input
                type="text"
                id="title"
                value={formData.title || ''}
                onChange={(e) => handleInputChange('title', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                required
              />
            </div>

            <div className="mb-4">
              <label htmlFor="slug" className="block text-sm font-medium text-gray-700 mb-1">
                Slug <span className="text-red-600">*</span>
              </label>
              <input
                type="text"
                id="slug"
                value={formData.slug || ''}
                onChange={(e) => handleInputChange('slug', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                required
              />
              <p className="text-xs text-gray-500 mt-1">
                Уникальный идентификатор для URL. Если не указан, будет сгенерирован из заголовка.
              </p>
            </div>

            <div className="mb-4">
              <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
                Описание
              </label>
              <textarea
                id="description"
                value={formData.description || ''}
                onChange={(e) => handleInputChange('description', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                rows={3}
              />
            </div>

            <div className="mb-4">
              <label htmlFor="image" className="block text-sm font-medium text-gray-700 mb-1">
                Изображение
              </label>
              <input
                type="text"
                id="image"
                value={formData.image || ''}
                onChange={(e) => handleInputChange('image', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                placeholder="URL изображения"
              />
              {formData.image && (
                <div className="mt-2">
                  <img 
                    src={formData.image} 
                    alt="Preview" 
                    className="w-24 h-24 object-cover rounded"
                  />
                </div>
              )}
            </div>
            
            {/* Поля типа контента */}
            {contentType.fields.map((field) => (
              <div key={field.id} className="mb-4">
                <label htmlFor={`field-${field.name}`} className="block text-sm font-medium text-gray-700 mb-1">
                  {field.label}
                  {field.required && <span className="text-red-600 ml-1">*</span>}
                </label>
                {renderField(field)}
              </div>
            ))}
          </div>
          
          <div className="mt-6 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => navigate(-1)}
              className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
            >
              Отмена
            </button>
            <button
              type="submit"
              disabled={saving}
              className="px-4 py-2 bg-primary text-black rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
            >
              {saving ? 'Сохранение...' : (isEditing ? 'Сохранить изменения' : 'Создать запись')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ContentForm; 
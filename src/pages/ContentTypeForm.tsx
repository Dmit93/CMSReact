import { useState, useEffect, FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { contentTypesAPI } from '../services/api';
import { Button } from '../components/ui/button';
import { Card } from '../components/ui/card';

interface ContentTypeFormData {
  name: string;
  label: string;
  description: string;
  slug: string;
  icon: string;
  menu_position: number;
  is_active: boolean;
}

const initialFormData: ContentTypeFormData = {
  name: '',
  label: '',
  description: '',
  slug: '',
  icon: 'file-text',
  menu_position: 0,
  is_active: true
};

const ContentTypeForm = () => {
  const { id } = useParams<{ id: string }>();
  const isEditMode = !!id;
  const navigate = useNavigate();
  
  const [formData, setFormData] = useState<ContentTypeFormData>(initialFormData);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [slugEdited, setSlugEdited] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      loadContentType();
    }
  }, [id]);

  // Автоматическое создание slug из label, если пользователь еще не редактировал slug
  useEffect(() => {
    if (!slugEdited && formData.label) {
      const generatedSlug = formData.label
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-') // Заменяем все не буквы/цифры на дефис
        .replace(/^-+|-+$/g, ''); // Удаляем начальные и конечные дефисы
      
      setFormData(prev => ({ ...prev, slug: generatedSlug }));
    }
  }, [formData.label, slugEdited]);

  const loadContentType = async () => {
    try {
      setLoading(true);
      const response = await contentTypesAPI.getById(Number(id));
      const contentType = response.data.data;
      
      if (contentType) {
        setFormData({
          name: contentType.name || '',
          label: contentType.label || '',
          description: contentType.description || '',
          slug: contentType.slug || '',
          icon: contentType.icon || 'file-text',
          menu_position: contentType.menu_position || 0,
          is_active: contentType.is_active !== undefined ? contentType.is_active : true
        });
        setSlugEdited(true); // Предполагаем, что slug уже был отредактирован для существующего типа
      }
    } catch (err) {
      console.error('Ошибка при загрузке типа контента:', err);
      setError('Не удалось загрузить тип контента. Пожалуйста, попробуйте позже.');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target;
    
    if (name === 'slug') {
      setSlugEdited(true);
    }
    
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' 
        ? (e.target as HTMLInputElement).checked 
        : name === 'menu_position' 
          ? parseInt(value, 10) || 0 
          : value
    }));
  };

  const handleCheckboxChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, checked } = e.target;
    setFormData(prev => ({ ...prev, [name]: checked }));
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    
    try {
      setSubmitting(true);
      setError(null);
      
      if (isEditMode) {
        await contentTypesAPI.update(Number(id), formData);
      } else {
        await contentTypesAPI.create(formData);
      }
      
      navigate('/content-types');
    } catch (err: any) {
      console.error('Ошибка при сохранении типа контента:', err);
      setError(
        err.response?.data?.message || 
        'Не удалось сохранить тип контента. Пожалуйста, проверьте введенные данные.'
      );
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center p-8">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold">
          {isEditMode ? 'Редактирование типа контента' : 'Создание нового типа контента'}
        </h1>
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      <Card>
        <form onSubmit={handleSubmit} className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Отображаемое имя*
                </label>
                <input
                  type="text"
                  name="label"
                  value={formData.label}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <p className="text-xs text-gray-500 mt-1">
                  Имя, которое будет отображаться в интерфейсе администратора
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Системное имя*
                </label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                  pattern="[a-z0-9_]+"
                  title="Только строчные латинские буквы, цифры и знак подчеркивания"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Используется в коде и API. Только латинские строчные буквы, цифры и знак подчеркивания
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Slug
                </label>
                <input
                  type="text"
                  name="slug"
                  value={formData.slug}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  pattern="[a-z0-9\-]+"
                  title="Только строчные латинские буквы, цифры и дефис"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Используется в URL. Только латинские строчные буквы, цифры и дефис
                </p>
              </div>
            </div>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Описание
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px]"
                ></textarea>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Позиция в меню
                </label>
                <input
                  type="number"
                  name="menu_position"
                  value={formData.menu_position}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  min="0"
                />
              </div>

              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="is_active"
                  name="is_active"
                  checked={formData.is_active}
                  onChange={handleCheckboxChange}
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                  Активен
                </label>
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-3 mt-6">
            <Button 
              type="button" 
              variant="secondary" 
              onClick={() => navigate('/content-types')}
              disabled={submitting}
            >
              Отмена
            </Button>
            <Button 
              type="submit"
              disabled={submitting}
            >
              {submitting ? 'Сохранение...' : 'Сохранить'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

export default ContentTypeForm; 
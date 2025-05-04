import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { contentTypesAPI } from '../services/api';
import { Button } from '../components/ui/button';
import { Card } from '../components/ui/card';

interface ContentType {
  id: number;
  name: string;
  label: string;
  description: string;
  slug: string;
  icon: string;
  menu_position: number;
  is_active: boolean;
}

const ContentTypes = () => {
  const navigate = useNavigate();
  const [contentTypes, setContentTypes] = useState<ContentType[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadContentTypes();
  }, []);

  const loadContentTypes = async () => {
    try {
      setLoading(true);
      const response = await contentTypesAPI.getAll();
      setContentTypes(response.data.data || []);
      setError(null);
    } catch (err) {
      console.error('Ошибка при загрузке типов контента:', err);
      setError('Не удалось загрузить типы контента. Пожалуйста, попробуйте позже.');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Вы уверены, что хотите удалить этот тип контента?')) {
      return;
    }

    try {
      await contentTypesAPI.delete(id);
      await loadContentTypes(); // Перезагружаем список после удаления
    } catch (err) {
      console.error('Ошибка при удалении типа контента:', err);
      setError('Не удалось удалить тип контента. Пожалуйста, попробуйте позже.');
    }
  };

  const renderContentTypeCard = (contentType: ContentType) => (
    <Card key={contentType.id} className="mb-4">
      <div className="p-4">
        <div className="flex justify-between items-center mb-2">
          <h3 className="text-lg font-medium">{contentType.label}</h3>
          <div className="flex space-x-2">
            <Button 
              variant="secondary" 
              onClick={() => navigate(`/content-types/${contentType.id}/fields`)}
            >
              Поля
            </Button>
            <Button 
              variant="secondary" 
              onClick={() => navigate(`/content-types/${contentType.id}/edit`)}
            >
              Редактировать
            </Button>
            <Button 
              variant="destructive" 
              onClick={() => handleDelete(contentType.id)}
            >
              Удалить
            </Button>
          </div>
        </div>
        <p className="text-gray-500 text-sm mb-2">
          Системное имя: <code>{contentType.name}</code>
        </p>
        {contentType.description && (
          <p className="text-sm mb-4">{contentType.description}</p>
        )}
        <div className="flex items-center mt-2">
          <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs ${
            contentType.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
          }`}>
            {contentType.is_active ? 'Активен' : 'Неактивен'}
          </span>
        </div>
      </div>
    </Card>
  );

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Типы контента</h1>
        <Button onClick={() => navigate('/content-types/create')}>
          Создать тип контента
        </Button>
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}

      {loading ? (
        <div className="flex justify-center p-8">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
        </div>
      ) : contentTypes.length === 0 ? (
        <div className="bg-gray-50 p-8 text-center rounded-lg">
          <p className="text-gray-500 mb-4">Типы контента не найдены</p>
          <Button onClick={() => navigate('/content-types/create')}>
            Создать первый тип контента
          </Button>
        </div>
      ) : (
        <div>
          {contentTypes.map(renderContentTypeCard)}
        </div>
      )}
    </div>
  );
};

export default ContentTypes; 
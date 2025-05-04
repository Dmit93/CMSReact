import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { contentTypesAPI } from '../services/api';
import { Button } from '../components/ui/button';
import { Card } from '../components/ui/card';
import { TableHead, TableRow, TableHeader, TableCell, TableBody, Table } from '../components/ui/table';

interface ContentType {
  id: number;
  name: string;
  label: string;
}

interface ContentTypeField {
  id: number;
  content_type_id: number;
  name: string;
  label: string;
  field_type: string;
  description: string;
  placeholder: string;
  default_value: string;
  options: Record<string, any>;
  is_required: boolean;
  validation: Record<string, any>;
  order: number;
}

const fieldTypeLabels: Record<string, string> = {
  'text': 'Текстовое поле',
  'textarea': 'Многострочное текстовое поле',
  'wysiwyg': 'Визуальный редактор',
  'number': 'Число',
  'range': 'Диапазон',
  'select': 'Выпадающий список',
  'checkbox': 'Флажки',
  'radio': 'Радиокнопки',
  'date': 'Дата',
  'time': 'Время',
  'datetime': 'Дата и время',
  'image': 'Изображение',
  'file': 'Файл',
  'gallery': 'Галерея'
};

const ContentTypeFields = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  
  const [contentType, setContentType] = useState<ContentType | null>(null);
  const [fields, setFields] = useState<ContentTypeField[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (id) {
      loadContentType();
      loadFields();
    }
  }, [id]);

  const loadContentType = async () => {
    try {
      const response = await contentTypesAPI.getById(Number(id));
      setContentType(response.data.data);
    } catch (err) {
      console.error('Ошибка при загрузке типа контента:', err);
      setError('Не удалось загрузить тип контента. Пожалуйста, попробуйте позже.');
    }
  };

  const loadFields = async () => {
    try {
      setLoading(true);
      const response = await contentTypesAPI.getContentTypeFields(Number(id));
      setFields(response.data.data || []);
      setError(null);
    } catch (err) {
      console.error('Ошибка при загрузке полей типа контента:', err);
      setError('Не удалось загрузить поля типа контента. Пожалуйста, попробуйте позже.');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (fieldId: number) => {
    if (!window.confirm('Вы уверены, что хотите удалить это поле?')) {
      return;
    }

    try {
      console.log('Удаление поля с ID:', fieldId, 'Тип:', typeof fieldId);
      const numericFieldId = Number(fieldId);
      console.log('Преобразованный ID:', numericFieldId, 'Тип:', typeof numericFieldId);
      await contentTypesAPI.deleteContentTypeField(numericFieldId);
      await loadFields(); // Перезагружаем список после удаления
    } catch (err) {
      console.error('Ошибка при удалении поля:', err);
      setError('Не удалось удалить поле. Пожалуйста, попробуйте позже.');
    }
  };

  const handleMoveField = async (fieldId: number, direction: 'up' | 'down') => {
    const fieldIndex = fields.findIndex(field => field.id === fieldId);
    if (fieldIndex === -1) return;
    
    const newFields = [...fields];
    const field = newFields[fieldIndex];
    
    if (direction === 'up' && fieldIndex > 0) {
      const prevField = newFields[fieldIndex - 1];
      const temp = prevField.order;
      prevField.order = field.order;
      field.order = temp;
      
      try {
        await contentTypesAPI.updateContentTypeField(field.id, { order: field.order });
        await contentTypesAPI.updateContentTypeField(prevField.id, { order: prevField.order });
        await loadFields(); // Перезагружаем список после изменения
      } catch (err) {
        console.error('Ошибка при изменении порядка полей:', err);
        setError('Не удалось изменить порядок полей. Пожалуйста, попробуйте позже.');
      }
    }
    
    if (direction === 'down' && fieldIndex < newFields.length - 1) {
      const nextField = newFields[fieldIndex + 1];
      const temp = nextField.order;
      nextField.order = field.order;
      field.order = temp;
      
      try {
        await contentTypesAPI.updateContentTypeField(field.id, { order: field.order });
        await contentTypesAPI.updateContentTypeField(nextField.id, { order: nextField.order });
        await loadFields(); // Перезагружаем список после изменения
      } catch (err) {
        console.error('Ошибка при изменении порядка полей:', err);
        setError('Не удалось изменить порядок полей. Пожалуйста, попробуйте позже.');
      }
    }
  };

  return (
    <div className="p-6">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold mb-1">Поля типа контента</h1>
          {contentType && (
            <p className="text-gray-500">
              {contentType.label} <code className="text-xs">({contentType.name})</code>
            </p>
          )}
        </div>
        <div className="flex space-x-3 mt-4 md:mt-0">
          <Button 
            variant="secondary" 
            onClick={() => navigate(`/content-types`)}
          >
            Назад к списку типов
          </Button>
          <Button 
            onClick={() => navigate(`/content-types/${id}/fields/create`)}
          >
            Добавить поле
          </Button>
        </div>
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
      ) : fields.length === 0 ? (
        <Card>
          <div className="p-8 text-center">
            <p className="text-gray-500 mb-4">Поля не найдены</p>
            <Button onClick={() => navigate(`/content-types/${id}/fields/create`)}>
              Создать первое поле
            </Button>
          </div>
        </Card>
      ) : (
        <Card>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-12">Порядок</TableHead>
                <TableHead>Название</TableHead>
                <TableHead>Системное имя</TableHead>
                <TableHead>Тип поля</TableHead>
                <TableHead>Обязательное</TableHead>
                <TableHead className="text-right">Действия</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {fields.sort((a, b) => a.order - b.order).map((field) => (
                <TableRow key={field.id}>
                  <TableCell>
                    <div className="flex items-center space-x-1">
                      <button
                        onClick={() => handleMoveField(field.id, 'up')}
                        disabled={field.order === Math.min(...fields.map(f => f.order))}
                        className="p-1 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="m18 15-6-6-6 6"/>
                        </svg>
                      </button>
                      <button
                        onClick={() => handleMoveField(field.id, 'down')}
                        disabled={field.order === Math.max(...fields.map(f => f.order))}
                        className="p-1 hover:bg-gray-100 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="m6 9 6 6 6-6"/>
                        </svg>
                      </button>
                    </div>
                  </TableCell>
                  <TableCell>{field.label}</TableCell>
                  <TableCell><code>{field.name}</code></TableCell>
                  <TableCell>{fieldTypeLabels[field.field_type] || field.field_type}</TableCell>
                  <TableCell>
                    {field.is_required ? (
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                        Да
                      </span>
                    ) : (
                      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                        Нет
                      </span>
                    )}
                  </TableCell>
                  <TableCell className="text-right">
                    <div className="flex justify-end space-x-2">
                      <Button
                        variant="secondary" 
                        size="sm"
                        onClick={() => navigate(`/content-types/${id}/fields/${field.id}/edit`)}
                      >
                        Редактировать
                      </Button>
                      <Button
                        variant="destructive" 
                        size="sm"
                        onClick={() => handleDelete(field.id)}
                      >
                        Удалить
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </Card>
      )}
    </div>
  );
};

export default ContentTypeFields; 
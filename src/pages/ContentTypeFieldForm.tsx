import { useState, useEffect, FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { contentTypesAPI } from '../services/api';
import { Button } from '../components/ui/button';
import { Card } from '../components/ui/card';
import { TextField, TextareaField, SelectField, CheckboxField } from '../components/fields';

// Типы полей
const fieldTypes = [
  { value: 'text', label: 'Текстовое поле' },
  { value: 'textarea', label: 'Многострочное текстовое поле' },
  { value: 'wysiwyg', label: 'Визуальный редактор' },
  { value: 'number', label: 'Число' },
  { value: 'range', label: 'Диапазон' },
  { value: 'select', label: 'Выпадающий список' },
  { value: 'checkbox', label: 'Флажки' },
  { value: 'radio', label: 'Радиокнопки' },
  { value: 'date', label: 'Дата' },
  { value: 'time', label: 'Время' },
  { value: 'datetime', label: 'Дата и время' },
  { value: 'image', label: 'Изображение' },
  { value: 'file', label: 'Файл' },
  { value: 'gallery', label: 'Галерея' }
];

interface ContentType {
  id: number;
  name: string;
  label: string;
}

interface FormData {
  name: string;
  label: string;
  field_type: string;
  description: string;
  placeholder: string;
  default_value: string;
  options: {
    items?: Array<{ value: string; label: string }>;
    min?: number;
    max?: number;
    step?: number;
    rows?: number;
    multiple?: boolean;
    [key: string]: any;
  };
  is_required: boolean;
  validation: {
    [key: string]: any;
  };
  order: number;
}

const initialFormData: FormData = {
  name: '',
  label: '',
  field_type: 'text',
  description: '',
  placeholder: '',
  default_value: '',
  options: {},
  is_required: false,
  validation: {},
  order: 0
};

const ContentTypeFieldForm = () => {
  const { contentTypeId, fieldId } = useParams<{ contentTypeId: string; fieldId: string }>();
  const isEditMode = !!fieldId;
  const navigate = useNavigate();

  const [contentType, setContentType] = useState<ContentType | null>(null);
  const [formData, setFormData] = useState<FormData>(initialFormData);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showOptionsEditor, setShowOptionsEditor] = useState(false);
  const [optionsText, setOptionsText] = useState('');

  // Загрузка информации о типе контента
  useEffect(() => {
    if (contentTypeId) {
      loadContentType();
    }
  }, [contentTypeId]);

  // Загрузка информации о поле при редактировании
  useEffect(() => {
    if (isEditMode && fieldId) {
      loadField();
    }
  }, [fieldId]);

  // Конвертация опций в/из JSON
  useEffect(() => {
    if (showOptionsEditor && formData.options) {
      setOptionsText(JSON.stringify(formData.options, null, 2));
    }
  }, [showOptionsEditor, formData.options]);

  // Следим за изменением типа поля для обновления опций
  useEffect(() => {
    // Устанавливаем дефолтные опции в зависимости от типа поля
    if (formData.field_type === 'textarea') {
      setFormData(prev => ({
        ...prev,
        options: { ...prev.options, rows: 4 }
      }));
    } else if (formData.field_type === 'number' || formData.field_type === 'range') {
      setFormData(prev => ({
        ...prev,
        options: { ...prev.options, min: 0, max: 100, step: 1 }
      }));
    } else if (
      formData.field_type === 'select' || 
      formData.field_type === 'checkbox' ||
      formData.field_type === 'radio'
    ) {
      if (!formData.options.items || formData.options.items.length === 0) {
        setFormData(prev => ({
          ...prev,
          options: {
            ...prev.options,
            items: [
              { value: 'option1', label: 'Опция 1' },
              { value: 'option2', label: 'Опция 2' }
            ]
          }
        }));
      }
    }
  }, [formData.field_type]);

  const loadContentType = async () => {
    try {
      const response = await contentTypesAPI.getById(Number(contentTypeId));
      setContentType(response.data.data);
    } catch (err) {
      console.error('Ошибка при загрузке типа контента:', err);
      setError('Не удалось загрузить информацию о типе контента.');
    }
  };

  const loadField = async () => {
    try {
      setLoading(true);
      console.log('Загрузка поля с ID:', fieldId, 'Тип:', typeof fieldId);
      const numericFieldId = Number(fieldId);
      console.log('Преобразованный ID:', numericFieldId, 'Тип:', typeof numericFieldId);
      
      const response = await contentTypesAPI.getContentTypeField(numericFieldId);
      console.log('Ответ API при загрузке поля:', response);
      
      const field = response.data.data;

      if (field) {
        setFormData({
          name: field.name || '',
          label: field.label || '',
          field_type: field.field_type || 'text',
          description: field.description || '',
          placeholder: field.placeholder || '',
          default_value: field.default_value || '',
          options: field.options || {},
          is_required: field.is_required || false,
          validation: field.validation || {},
          order: field.order || 0
        });
      } else {
        setError('Поле не найдено в ответе API');
      }
    } catch (err: any) {
      console.error('Ошибка при загрузке поля:', err);
      console.error('Детали ошибки:', err.response?.data || err.message);
      setError(
        err.response?.data?.message || 
        'Не удалось загрузить информацию о поле. Пожалуйста, попробуйте позже.'
      );
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target as HTMLInputElement;

    if (type === 'checkbox') {
      const checked = (e.target as HTMLInputElement).checked;
      setFormData(prev => ({ ...prev, [name]: checked }));
    } else {
      setFormData(prev => ({ ...prev, [name]: value }));
    }
  };

  const handleOptionsChange = (name: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      options: {
        ...prev.options,
        [name]: value
      }
    }));
  };

  const handleOptionItemChange = (index: number, field: 'value' | 'label', value: string) => {
    setFormData(prev => {
      const items = [...(prev.options.items || [])];
      items[index] = { ...items[index], [field]: value };
      
      return {
        ...prev,
        options: {
          ...prev.options,
          items
        }
      };
    });
  };

  const addOptionItem = () => {
    setFormData(prev => {
      const items = [...(prev.options.items || [])];
      items.push({ value: `option${items.length + 1}`, label: `Опция ${items.length + 1}` });
      
      return {
        ...prev,
        options: {
          ...prev.options,
          items
        }
      };
    });
  };

  const removeOptionItem = (index: number) => {
    setFormData(prev => {
      const items = [...(prev.options.items || [])];
      items.splice(index, 1);
      
      return {
        ...prev,
        options: {
          ...prev.options,
          items
        }
      };
    });
  };

  const applyOptionsJSON = () => {
    try {
      const parsedOptions = JSON.parse(optionsText);
      setFormData(prev => ({
        ...prev,
        options: parsedOptions
      }));
      setShowOptionsEditor(false);
    } catch (err) {
      alert('Некорректный JSON формат. Пожалуйста, проверьте синтаксис.');
    }
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    
    try {
      setSubmitting(true);
      setError(null);
      
      // Подготовка данных для отправки
      const fieldData = {
        ...formData,
        content_type_id: Number(contentTypeId),
        options: typeof formData.options === 'string' 
          ? JSON.parse(formData.options) 
          : formData.options,
        validation: typeof formData.validation === 'string' 
          ? JSON.parse(formData.validation) 
          : formData.validation
      };
      
      console.log('Отправка данных поля:', fieldData);
      console.log('contentTypeId:', contentTypeId);
      
      let result;
      if (isEditMode) {
        console.log('Обновление поля с ID:', fieldId);
        result = await contentTypesAPI.updateContentTypeField(Number(fieldId), fieldData);
      } else {
        console.log('Создание нового поля для типа контента с ID:', contentTypeId);
        result = await contentTypesAPI.createContentTypeField(Number(contentTypeId), fieldData);
      }
      
      console.log('Ответ API:', result);
      
      if (result.data && result.data.success) {
        console.log('Поле успешно сохранено:', result.data);
        navigate(`/content-types/${contentTypeId}/fields`);
      } else {
        console.error('Ошибка в ответе API:', result);
        setError(result.data?.message || 'Не удалось сохранить поле');
      }
    } catch (err: any) {
      console.error('Ошибка при сохранении поля:', err);
      console.error('Детали ошибки:', err.response?.data || err.message);
      setError(
        err.response?.data?.message || 
        'Не удалось сохранить поле. Пожалуйста, проверьте введенные данные.'
      );
    } finally {
      setSubmitting(false);
    }
  };

  // Рендер разных опций в зависимости от типа поля
  const renderFieldOptions = () => {
    switch (formData.field_type) {
      case 'textarea':
      case 'wysiwyg':
        return (
          <div className="mt-4 p-4 border border-gray-200 rounded-md">
            <h3 className="text-md font-medium mb-3">Настройки текстовой области</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <TextField
                id="options-rows"
                name="rows"
                label="Количество строк"
                value={formData.options.rows?.toString() || '4'}
                onChange={(e) => handleOptionsChange('rows', parseInt(e.target.value) || 4)}
                type="number"
                min="2"
                max="20"
              />
            </div>
          </div>
        );
      
      case 'number':
      case 'range':
        return (
          <div className="mt-4 p-4 border border-gray-200 rounded-md">
            <h3 className="text-md font-medium mb-3">Настройки числового поля</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <TextField
                id="options-min"
                name="min"
                label="Минимальное значение"
                value={formData.options.min?.toString() || '0'}
                onChange={(e) => handleOptionsChange('min', parseInt(e.target.value) || 0)}
                type="number"
              />
              <TextField
                id="options-max"
                name="max"
                label="Максимальное значение"
                value={formData.options.max?.toString() || '100'}
                onChange={(e) => handleOptionsChange('max', parseInt(e.target.value) || 100)}
                type="number"
              />
              <TextField
                id="options-step"
                name="step"
                label="Шаг"
                value={formData.options.step?.toString() || '1'}
                onChange={(e) => handleOptionsChange('step', parseInt(e.target.value) || 1)}
                type="number"
                min="0.1"
              />
            </div>
          </div>
        );
      
      case 'select':
      case 'checkbox':
      case 'radio':
        return (
          <div className="mt-4 p-4 border border-gray-200 rounded-md">
            <div className="flex justify-between items-center mb-3">
              <h3 className="text-md font-medium">Настройки вариантов выбора</h3>
              <Button
                type="button"
                onClick={addOptionItem}
                size="sm"
              >
                Добавить опцию
              </Button>
            </div>
            
            {formData.options.items?.map((item, index) => (
              <div key={index} className="flex items-center gap-2 mb-2">
                <TextField
                  id={`option-value-${index}`}
                  name={`option-value-${index}`}
                  label={index === 0 ? "Значение" : ""}
                  value={item.value}
                  onChange={(e) => handleOptionItemChange(index, 'value', e.target.value)}
                  className="flex-1"
                />
                <TextField
                  id={`option-label-${index}`}
                  name={`option-label-${index}`}
                  label={index === 0 ? "Метка" : ""}
                  value={item.label}
                  onChange={(e) => handleOptionItemChange(index, 'label', e.target.value)}
                  className="flex-1"
                />
                <Button
                  type="button"
                  variant="destructive"
                  size="sm"
                  onClick={() => removeOptionItem(index)}
                  className={index === 0 ? 'mt-6' : ''}
                >
                  Удалить
                </Button>
              </div>
            ))}
            
            {formData.field_type === 'select' && (
              <div className="mt-3">
                <label className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    checked={formData.options.multiple || false}
                    onChange={(e) => handleOptionsChange('multiple', e.target.checked)}
                    className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <span className="text-sm font-medium text-gray-700">
                    Множественный выбор
                  </span>
                </label>
              </div>
            )}
          </div>
        );
      
      default:
        return null;
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
          {isEditMode ? 'Редактирование поля' : 'Создание нового поля'}
        </h1>
        {contentType && (
          <p className="text-gray-500">
            Тип контента: {contentType.label} <code className="text-xs">({contentType.name})</code>
          </p>
        )}
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
              <TextField
                id="label"
                name="label"
                label="Отображаемое имя*"
                value={formData.label}
                onChange={handleChange}
                required
                description="Имя, которое будет отображаться в интерфейсе администратора"
              />

              <TextField
                id="name"
                name="name"
                label="Системное имя*"
                value={formData.name}
                onChange={handleChange}
                required
                pattern="[a-z0-9_]+"
                description="Используется в коде и API. Только латинские строчные буквы, цифры и знак подчеркивания"
              />

              <SelectField
                id="field_type"
                name="field_type"
                label="Тип поля*"
                value={formData.field_type}
                onChange={handleChange}
                options={fieldTypes}
                required
              />

              <TextareaField
                id="description"
                name="description"
                label="Описание"
                value={formData.description}
                onChange={handleChange}
                description="Пояснение для администратора, как использовать это поле"
              />
            </div>

            <div className="space-y-4">
              <TextField
                id="placeholder"
                name="placeholder"
                label="Плейсхолдер"
                value={formData.placeholder}
                onChange={handleChange}
                description="Текст-подсказка в пустом поле"
              />

              <TextField
                id="default_value"
                name="default_value"
                label="Значение по умолчанию"
                value={formData.default_value}
                onChange={handleChange}
              />

              <div className="flex items-center space-x-2 pt-4">
                <input
                  type="checkbox"
                  id="is_required"
                  name="is_required"
                  checked={formData.is_required}
                  onChange={handleChange}
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label htmlFor="is_required" className="text-sm font-medium text-gray-700">
                  Обязательное поле
                </label>
              </div>
            </div>
          </div>

          {renderFieldOptions()}

          <div className="mt-4">
            <div className="flex justify-between items-center">
              <h3 className="text-md font-medium">Расширенные настройки</h3>
              <Button
                type="button"
                variant="secondary"
                size="sm"
                onClick={() => setShowOptionsEditor(!showOptionsEditor)}
              >
                {showOptionsEditor ? 'Скрыть редактор JSON' : 'Редактировать JSON опций'}
              </Button>
            </div>

            {showOptionsEditor && (
              <div className="mt-3">
                <TextareaField
                  id="options-json"
                  name="options-json"
                  label="Настройки в формате JSON"
                  value={optionsText}
                  onChange={(e) => setOptionsText(e.target.value)}
                  rows={10}
                />
                <Button
                  type="button"
                  onClick={applyOptionsJSON}
                  className="mt-2"
                  size="sm"
                >
                  Применить JSON
                </Button>
              </div>
            )}
          </div>

          <div className="flex justify-end space-x-3 mt-6">
            <Button 
              type="button" 
              variant="secondary" 
              onClick={() => navigate(`/content-types/${contentTypeId}/fields`)}
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

export default ContentTypeFieldForm; 
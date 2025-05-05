import React, { useState, useEffect } from 'react';
import { Plus, Search, Edit, Trash, FolderTree, X, Check } from 'lucide-react';
import { shopAPI } from '../../services/api';

// Интерфейс для категории
interface Category {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  products_count?: number;
  status: string;
  children?: Category[];
  description?: string;
}

// Интерфейс для модальной формы
interface CategoryFormData {
  id?: number;
  name: string;
  slug: string;
  parent_id: number | null;
  status: string;
  description?: string;
}

// Функция для построения дерева категорий
const buildCategoryTree = (categories: Category[], parentId: number | null = null): Category[] => {
  return categories
    .filter(category => category.parent_id === parentId)
    .map(category => ({
      ...category,
      children: buildCategoryTree(categories, category.id)
    }));
};

// Компонент для строки категории
const CategoryRow = ({ 
  category, 
  level = 0, 
  onEdit, 
  onDelete 
}: { 
  category: Category; 
  level?: number; 
  onEdit: (id: number) => void; 
  onDelete: (id: number) => void; 
}) => {
  return (
    <>
      <tr>
        <td className="px-6 py-4 whitespace-nowrap">
          <div className="text-sm font-medium text-gray-900" style={{ paddingLeft: `${level * 20}px` }}>
            {level > 0 && <span className="text-gray-400 mr-2">└─</span>}
            {category.name}
          </div>
        </td>
        <td className="px-6 py-4 whitespace-nowrap">
          <div className="text-sm text-gray-500">{category.slug}</div>
        </td>
        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
          {category.products_count || 0}
        </td>
        <td className="px-6 py-4 whitespace-nowrap">
          <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
            category.status === 'active' 
              ? 'bg-green-100 text-green-800' 
              : 'bg-gray-100 text-gray-800'
          }`}>
            {category.status === 'active' ? 'Активна' : 'Неактивна'}
          </span>
        </td>
        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
          <button 
            className="text-indigo-600 hover:text-indigo-900 mr-3"
            onClick={() => onEdit(category.id)}
          >
            <Edit className="w-4 h-4" />
          </button>
          <button 
            className="text-red-600 hover:text-red-900"
            onClick={() => onDelete(category.id)}
          >
            <Trash className="w-4 h-4" />
          </button>
        </td>
      </tr>
      {/* Рендеринг дочерних категорий */}
      {category.children && category.children.map((child: Category) => (
        <CategoryRow 
          key={child.id} 
          category={child} 
          level={level + 1} 
          onEdit={onEdit} 
          onDelete={onDelete} 
        />
      ))}
    </>
  );
};

const ShopCategories: React.FC = () => {
  const [categories, setCategories] = useState<Category[]>([]);
  const [categoryTree, setCategoryTree] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  
  // Состояние для модальной формы
  const [showModal, setShowModal] = useState(false);
  const [formData, setFormData] = useState<CategoryFormData>({
    name: '',
    slug: '',
    parent_id: null,
    status: 'published',
    description: ''
  });
  const [formMode, setFormMode] = useState<'create' | 'edit'>('create');
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [submitLoading, setSubmitLoading] = useState(false);

  // Загрузка данных
  const loadData = async () => {
    try {
      setLoading(true);
      
      // Получаем категории
      const response = await shopAPI.getCategories();
      console.log('Получены данные о категориях:', response);
      
      // Проверяем структуру ответа и устанавливаем категории
      if (response && response.data) {
        // Если данные в поле data.data (вложенная структура)
        if (response.data.data && Array.isArray(response.data.data)) {
          setCategories(response.data.data);
          // Строим дерево категорий
          const tree = buildCategoryTree(response.data.data);
          setCategoryTree(tree);
        } 
        // Если данные напрямую в поле data
        else if (Array.isArray(response.data)) {
          setCategories(response.data);
          // Строим дерево категорий
          const tree = buildCategoryTree(response.data);
          setCategoryTree(tree);
        } 
        else {
          console.error('Неожиданная структура данных категорий:', response.data);
          setCategories([]);
          setCategoryTree([]);
        }
      } else {
        console.error('Нет данных в ответе API для категорий');
        setCategories([]);
        setCategoryTree([]);
      }
      
      setLoading(false);
    } catch (error) {
      console.error('Ошибка при загрузке категорий:', error);
      setCategories([]);
      setCategoryTree([]);
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [searchTerm]);

  // Открытие формы для создания новой категории
  const handleOpenCreateForm = () => {
    setFormData({
      name: '',
      slug: '',
      parent_id: null,
      status: 'published',
      description: ''
    });
    setFormErrors({});
    setFormMode('create');
    setShowModal(true);
  };

  // Открытие формы для редактирования категории
  const handleOpenEditForm = async (id: number) => {
    try {
      setSubmitLoading(true);
      
      // Находим категорию по ID (сначала ищем в текущем дереве категорий)
      const findCategoryById = (categories: Category[], id: number): Category | null => {
        for (const category of categories) {
          if (category.id === id) {
            return category;
          }
          if (category.children && category.children.length > 0) {
            const found = findCategoryById(category.children, id);
            if (found) return found;
          }
        }
        return null;
      };
      
      // Сначала попробуем найти категорию в локальных данных
      let category = findCategoryById(categoryTree, id);
      
      // Если не нашли, запрашиваем с сервера
      if (!category) {
        const response = await shopAPI.getCategory(id);
        category = response.data.data;
      }
      
      if (!category) {
        throw new Error(`Категория с ID ${id} не найдена`);
      }
      
      // Маппинг данных для формы редактирования
      setFormData({
        id: category.id,
        name: category.name,
        slug: category.slug,
        parent_id: category.parent_id,
        status: category.status, // Будет использовать значения из API
        description: category.description || ''
      });
      
      setFormErrors({});
      setFormMode('edit');
      setShowModal(true);
      setSubmitLoading(false);
    } catch (error) {
      console.error('Ошибка при загрузке категории:', error);
      alert('Не удалось загрузить категорию. Пожалуйста, попробуйте еще раз.');
      setSubmitLoading(false);
    }
  };

  // Валидация формы
  const validateForm = (): boolean => {
    const errors: Record<string, string> = {};
    
    if (!formData.name.trim()) {
      errors.name = 'Название категории обязательно';
    }
    
    if (!formData.slug.trim()) {
      errors.slug = 'URL обязателен';
    } else if (!/^[a-z0-9-]+$/.test(formData.slug)) {
      errors.slug = 'URL может содержать только строчные буквы, цифры и дефис';
    }
    
    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  // Обработчик отправки формы
  const handleSubmitForm = async () => {
    if (!validateForm()) {
      return;
    }
    
    try {
      setSubmitLoading(true);
      
      if (formMode === 'create') {
        // Создание новой категории
        await shopAPI.createCategory(formData);
      } else {
        // Редактирование существующей категории
        await shopAPI.updateCategory(formData.id!, formData);
      }
      
      // Перезагружаем данные и закрываем форму
      await loadData();
      setShowModal(false);
      setSubmitLoading(false);
    } catch (error) {
      console.error('Ошибка при сохранении категории:', error);
      setSubmitLoading(false);
    }
  };

  // Обработчик генерации slug из названия
  const generateSlug = () => {
    if (!formData.name) return;
    
    const slug = formData.name
      .toLowerCase()
      .replace(/[^\w\s-]/g, '') // Удаляем специальные символы
      .replace(/\s+/g, '-')     // Заменяем пробелы на дефисы
      .replace(/--+/g, '-')     // Заменяем множественные дефисы на одиночные
      .trim();
      
    setFormData({
      ...formData,
      slug
    });
  };

  // Обработчик изменения полей формы
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    
    if (name === 'parent_id') {
      // Обработка выбора родительской категории
      setFormData({
        ...formData,
        parent_id: value === '' ? null : parseInt(value)
      });
    } else {
      setFormData({
        ...formData,
        [name]: value
      });
    }
  };

  // Обработчик удаления категории
  const handleDelete = async (id: number) => {
    // Проверяем, есть ли у категории дочерние элементы
    const category = categories.find(cat => cat.id === id);
    const hasChildren = categories.some(cat => cat.parent_id === id);
    
    if (hasChildren) {
      alert('Невозможно удалить категорию, которая содержит подкатегории. Сначала удалите все подкатегории.');
      return;
    }
    
    if (window.confirm('Вы уверены, что хотите удалить эту категорию?')) {
      try {
        await shopAPI.deleteCategory(id);
        // Обновляем список категорий
        await loadData();
      } catch (error) {
        console.error('Ошибка при удалении категории:', error);
      }
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Категории товаров</h1>
        <button 
          className="bg-indigo-600 text-white px-4 py-2 rounded-md flex items-center hover:bg-indigo-700"
          onClick={handleOpenCreateForm}
        >
          <Plus className="w-4 h-4 mr-2" />
          Добавить категорию
        </button>
      </div>

      {/* Поиск */}
      <div className="mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Поиск категорий..."
            className="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      {/* Таблица категорий */}
      {loading ? (
        <div className="flex justify-center items-center p-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
        </div>
      ) : categoryTree.length === 0 ? (
        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
          <p className="flex items-center">
            <FolderTree className="mr-2" />
            Категории не найдены. Создайте новую категорию или измените параметры поиска.
          </p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Название
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Slug
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Товаров
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Статус
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Действия
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {searchTerm ? (
                // Если есть поисковый запрос, показываем плоский список
                categoryTree.map((category) => (
                  <tr key={category.id}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">{category.name}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-500">{category.slug}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {category.products_count || 0}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        category.status === 'published' 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {category.status === 'published' ? 'Активна' : 'Неактивна'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button 
                        className="text-indigo-600 hover:text-indigo-900 mr-3"
                        onClick={() => handleOpenEditForm(category.id)}
                      >
                        <Edit className="w-4 h-4" />
                      </button>
                      <button 
                        className="text-red-600 hover:text-red-900"
                        onClick={() => handleDelete(category.id)}
                      >
                        <Trash className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                // Иначе показываем дерево категорий
                categoryTree.map((category) => (
                  <CategoryRow 
                    key={category.id} 
                    category={category} 
                    onEdit={handleOpenEditForm} 
                    onDelete={handleDelete} 
                  />
                ))
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* Модальное окно для создания/редактирования категории */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-8 max-w-xl w-full max-h-[80vh] overflow-y-auto">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-xl font-bold">
                {formMode === 'create' ? 'Добавление категории' : 'Редактирование категории'}
              </h2>
              <button
                className="text-gray-500 hover:text-gray-700"
                onClick={() => setShowModal(false)}
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="space-y-4">
              {/* Название категории */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Название *
                </label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleInputChange}
                  onBlur={() => {
                    if (!formData.slug && formData.name) {
                      generateSlug();
                    }
                  }}
                  className={`w-full px-3 py-2 border rounded-md ${formErrors.name ? 'border-red-500' : 'border-gray-300'}`}
                />
                {formErrors.name && (
                  <p className="mt-1 text-sm text-red-500">{formErrors.name}</p>
                )}
              </div>

              {/* URL (slug) */}
              <div>
                <div className="flex justify-between items-center mb-1">
                  <label className="block text-sm font-medium text-gray-700">
                    URL (slug) *
                  </label>
                  <button
                    type="button"
                    onClick={generateSlug}
                    className="text-xs text-indigo-600 hover:text-indigo-800"
                  >
                    Сгенерировать из названия
                  </button>
                </div>
                <input
                  type="text"
                  name="slug"
                  value={formData.slug}
                  onChange={handleInputChange}
                  className={`w-full px-3 py-2 border rounded-md ${formErrors.slug ? 'border-red-500' : 'border-gray-300'}`}
                  placeholder="category-url"
                />
                {formErrors.slug && (
                  <p className="mt-1 text-sm text-red-500">{formErrors.slug}</p>
                )}
              </div>

              {/* Родительская категория */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Родительская категория
                </label>
                <select
                  name="parent_id"
                  value={formData.parent_id === null ? '' : formData.parent_id}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md"
                >
                  <option value="">Нет родительской категории</option>
                  {categories
                    // Исключаем текущую категорию и её дочерние из списка возможных родителей
                    .filter(cat => 
                      !formData.id || 
                      (cat.id !== formData.id && 
                       // Также исключаем дочерние категории если редактируем
                       !(formMode === 'edit' && buildCategoryTree([cat], formData.id).length > 0))
                    )
                    .map(category => (
                      <option key={category.id} value={category.id}>
                        {category.name}
                      </option>
                    ))
                  }
                </select>
              </div>

              {/* Статус */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Статус
                </label>
                <select
                  name="status"
                  value={formData.status}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md"
                >
                  <option value="active">Активна</option>
                  <option value="inactive">Неактивна</option>
                </select>
              </div>

              {/* Описание */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Описание
                </label>
                <textarea
                  name="description"
                  value={formData.description || ''}
                  onChange={handleInputChange}
                  rows={4}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md"
                ></textarea>
              </div>

              {/* Кнопки действий */}
              <div className="flex justify-end space-x-3 pt-4">
                <button
                  className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                  onClick={() => setShowModal(false)}
                  disabled={submitLoading}
                >
                  Отмена
                </button>
                <button
                  className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center"
                  onClick={handleSubmitForm}
                  disabled={submitLoading}
                >
                  {submitLoading ? (
                    <>
                      <div className="animate-spin rounded-full h-4 w-4 border-t-2 border-white mr-2"></div>
                      Сохранение...
                    </>
                  ) : (
                    <>
                      <Check className="w-4 h-4 mr-2" />
                      Сохранить
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ShopCategories; 
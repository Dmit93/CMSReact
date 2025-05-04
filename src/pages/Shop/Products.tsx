import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Plus, Search, Edit, Trash, Package, X, Check } from 'lucide-react';
import { shopAPI } from '../../services/api';

// Интерфейс для товара
interface Product {
  id: number;
  title: string;
  price: number;
  stock: number;
  status: string;
  sku: string;
  category_id?: number;
  categories?: Category[];
  slug?: string;
  description?: string;
}

// Интерфейс для категории
interface Category {
  id: number;
  name: string;
  slug: string;
}

// Интерфейс для модальной формы
interface ProductFormData {
  id?: number;
  title: string;
  price: number;
  stock: number;
  status: string;
  sku: string;
  category_id?: number;
  slug?: string;
  description?: string;
}

const ShopProducts: React.FC = () => {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  
  // Состояние для модальной формы
  const [showModal, setShowModal] = useState(false);
  const [formData, setFormData] = useState<ProductFormData>({
    title: '',
    price: 0,
    stock: 0,
    status: 'published',
    sku: '',
    category_id: undefined,
    description: '',
    slug: ''
  });
  const [formMode, setFormMode] = useState<'create' | 'edit'>('create');
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [submitLoading, setSubmitLoading] = useState(false);

  // Загрузка данных
  const loadData = async () => {
    try {
      setLoading(true);
      console.log('Начало загрузки товаров...');
      
      // Получаем товары
      const productsResponse = await shopAPI.getProducts();
      console.log('Ответ API товаров:', productsResponse);
      
      // Проверяем структуру ответа и устанавливаем товары
      if (productsResponse && productsResponse.data) {
        console.log('Структура ответа товаров:', typeof productsResponse.data, Object.keys(productsResponse.data));
        
        // Если данные в поле data.data (вложенная структура)
        if (productsResponse.data.data && Array.isArray(productsResponse.data.data)) {
          console.log(`Найдено ${productsResponse.data.data.length} товаров во вложенной структуре`);
          setProducts(productsResponse.data.data);
        } 
        // Если данные напрямую в поле data
        else if (Array.isArray(productsResponse.data)) {
          console.log(`Найдено ${productsResponse.data.length} товаров в прямой структуре`);
          setProducts(productsResponse.data);
        } 
        // Если структура не соответствует ожидаемой, но есть поле success
        else if (productsResponse.data.success && productsResponse.data.debug_info) {
          console.log('Получен успешный ответ, но товары отсутствуют:', productsResponse.data.debug_info);
          setProducts([]);
          alert(`Товары не найдены. ${productsResponse.data.debug_info.message}`);
        }
        else {
          console.error('Неожиданная структура данных товаров:', productsResponse.data);
          setProducts([]);
          alert('Ошибка при загрузке товаров: неожиданная структура данных');
        }
      } else {
        console.error('Нет данных в ответе API для товаров');
        setProducts([]);
        alert('Ошибка при загрузке товаров: нет данных в ответе');
      }
      
      // Получаем категории для выпадающего списка формы
      try {
        const categoriesResponse = await shopAPI.getCategories();
        console.log('Ответ API категорий:', categoriesResponse);
        
        // Проверяем структуру ответа и устанавливаем категории
        if (categoriesResponse && categoriesResponse.data) {
          console.log('Структура ответа категорий:', typeof categoriesResponse.data, Object.keys(categoriesResponse.data));
          
          // Если данные в поле data.data (вложенная структура)
          if (categoriesResponse.data.data && Array.isArray(categoriesResponse.data.data)) {
            console.log(`Найдено ${categoriesResponse.data.data.length} категорий во вложенной структуре`);
            setCategories(categoriesResponse.data.data);
          } 
          // Если данные напрямую в поле data
          else if (Array.isArray(categoriesResponse.data)) {
            console.log(`Найдено ${categoriesResponse.data.length} категорий в прямой структуре`);
            setCategories(categoriesResponse.data);
          } 
          else {
            console.error('Неожиданная структура данных категорий:', categoriesResponse.data);
            setCategories([]);
          }
        } else {
          console.error('Нет данных в ответе API для категорий');
          setCategories([]);
        }
      } catch (categoryError) {
        console.error('Ошибка при загрузке категорий:', categoryError);
        setCategories([]);
      }
      
      setLoading(false);
      console.log('Загрузка данных завершена');
    } catch (error: any) {
      console.error('Ошибка при загрузке данных:', error);
      if (error.response) {
        console.error('Статус ответа:', error.response.status);
        console.error('Данные ответа:', error.response.data);
      } else if (error.request) {
        console.error('Запрос отправлен, но ответ не получен:', error.request);
      } else {
        console.error('Произошла ошибка при настройке запроса:', error.message);
      }
      
      setProducts([]);
      setCategories([]);
      setLoading(false);
      alert('Ошибка при загрузке данных. Проверьте консоль для подробностей.');
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  // Фильтрация продуктов по поисковому запросу
  const filteredProducts = products.filter(product => 
    product.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    product.sku?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // Открытие формы для создания нового товара
  const handleOpenCreateForm = () => {
    setFormData({
      title: '',
      price: 0,
      stock: 0,
      status: 'published',
      sku: '',
      category_id: categories.length > 0 ? categories[0].id : undefined,
      description: '',
      slug: ''
    });
    setFormErrors({});
    setFormMode('create');
    setShowModal(true);
  };

  // Открытие формы для редактирования товара
  const handleOpenEditForm = async (id: number) => {
    try {
      setSubmitLoading(true);
      console.log(`Открытие формы редактирования товара с ID: ${id}`);
      
      // Загружаем полные данные о товаре
      const response = await shopAPI.getProduct(id);
      console.log(`Получены данные товара для редактирования:`, response);
      
      // Проверяем структуру ответа
      if (!response || !response.data) {
        console.error('Ответ не содержит данных');
        alert('Ошибка при загрузке товара. Пожалуйста, попробуйте позже.');
        setSubmitLoading(false);
        return;
      }
      
      let product;
      if (response.data.data) {
        // Если данные в поле data.data (вложенная структура)
        product = response.data.data;
      } else if (response.data) {
        // Если данные напрямую в поле data
        product = response.data;
      } else {
        console.error('Неожиданная структура данных товара:', response.data);
        alert('Ошибка при загрузке товара. Пожалуйста, попробуйте позже.');
        setSubmitLoading(false);
        return;
      }
      
      console.log('Данные товара для формы:', product);
      
      // Установка данных формы
      setFormData({
        id: product.id,
        title: product.title || '',
        price: product.price || 0,
        stock: product.stock || 0,
        status: product.status || 'published',
        sku: product.sku || '',
        category_id: product.category_id || undefined,
        description: product.description || '',
        slug: product.slug || ''
      });
      
      setFormErrors({});
      setFormMode('edit');
      setShowModal(true);
      setSubmitLoading(false);
    } catch (error) {
      console.error('Ошибка при загрузке товара:', error);
      alert('Не удалось загрузить данные товара. Пожалуйста, проверьте соединение и попробуйте снова.');
      setSubmitLoading(false);
    }
  };

  // Валидация формы
  const validateForm = (): boolean => {
    const errors: Record<string, string> = {};
    
    if (!formData.title.trim()) {
      errors.title = 'Название товара обязательно';
    }
    
    if (formData.price <= 0) {
      errors.price = 'Цена должна быть больше нуля';
    }
    
    if (formData.stock < 0) {
      errors.stock = 'Количество не может быть отрицательным';
    }
    
    if (!formData.sku.trim()) {
      errors.sku = 'Артикул обязателен';
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
        // Создание нового товара
        await shopAPI.createProduct(formData);
      } else {
        // Редактирование существующего товара
        await shopAPI.updateProduct(formData.id!, formData);
      }
      
      // Перезагружаем данные и закрываем форму
      await loadData();
      setShowModal(false);
      setSubmitLoading(false);
    } catch (error) {
      console.error('Ошибка при сохранении товара:', error);
      setSubmitLoading(false);
    }
  };

  // Обработчик удаления товара
  const handleDelete = async (id: number) => {
    if (window.confirm('Вы уверены, что хотите удалить этот товар?')) {
      try {
        await shopAPI.deleteProduct(id);
        // Обновляем список товаров
        await loadData();
      } catch (error) {
        console.error('Ошибка при удалении товара:', error);
      }
    }
  };

  // Обработчик изменения полей формы
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value, type } = e.target;
    
    // Разные обработчики в зависимости от типа поля
    if (type === 'number') {
      setFormData({
        ...formData,
        [name]: parseFloat(value) || 0
      });
    } else {
      setFormData({
        ...formData,
        [name]: value
      });
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Товары</h1>
        <button 
          className="bg-indigo-600 text-white px-4 py-2 rounded-md flex items-center hover:bg-indigo-700"
          onClick={handleOpenCreateForm}
        >
          <Plus className="w-4 h-4 mr-2" />
          Добавить товар
        </button>
      </div>

      {/* Поиск */}
      <div className="mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Поиск товаров..."
            className="pl-10 pr-4 py-2 border rounded-md w-full focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
      </div>

      {/* Таблица товаров */}
      {loading ? (
        <div className="flex justify-center items-center p-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
        </div>
      ) : filteredProducts.length === 0 ? (
        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
          <p className="flex items-center">
            <Package className="mr-2" />
            Товары не найдены. Попробуйте изменить параметры поиска или добавьте новый товар.
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
                  Артикул
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Цена
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Наличие
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
              {filteredProducts.map((product) => (
                <tr key={product.id}>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">{product.title}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-500">{product.sku}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{product.price.toLocaleString()} ₽</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{product.stock} шт.</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                      product.status === 'active' 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-gray-100 text-gray-800'
                    }`}>
                      {product.status === 'active' ? 'Активен' : 'Неактивен'}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button 
                      className="text-indigo-600 hover:text-indigo-900 mr-3"
                      onClick={() => handleOpenEditForm(product.id)}
                    >
                      <Edit className="w-4 h-4" />
                    </button>
                    <button 
                      className="text-red-600 hover:text-red-900"
                      onClick={() => handleDelete(product.id)}
                    >
                      <Trash className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Модальное окно для создания/редактирования товара */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-xl font-bold">
                {formMode === 'create' ? 'Добавление товара' : 'Редактирование товара'}
              </h2>
              <button
                className="text-gray-500 hover:text-gray-700"
                onClick={() => setShowModal(false)}
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            <div className="space-y-4">
              {/* Название товара */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Название *
                </label>
                <input
                  type="text"
                  name="title"
                  value={formData.title}
                  onChange={handleInputChange}
                  className={`w-full px-3 py-2 border rounded-md ${formErrors.title ? 'border-red-500' : 'border-gray-300'}`}
                />
                {formErrors.title && (
                  <p className="mt-1 text-sm text-red-500">{formErrors.title}</p>
                )}
              </div>

              {/* Артикул */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Артикул *
                </label>
                <input
                  type="text"
                  name="sku"
                  value={formData.sku}
                  onChange={handleInputChange}
                  className={`w-full px-3 py-2 border rounded-md ${formErrors.sku ? 'border-red-500' : 'border-gray-300'}`}
                />
                {formErrors.sku && (
                  <p className="mt-1 text-sm text-red-500">{formErrors.sku}</p>
                )}
              </div>

              {/* Цена */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Цена *
                </label>
                <input
                  type="number"
                  name="price"
                  value={formData.price}
                  onChange={handleInputChange}
                  className={`w-full px-3 py-2 border rounded-md ${formErrors.price ? 'border-red-500' : 'border-gray-300'}`}
                />
                {formErrors.price && (
                  <p className="mt-1 text-sm text-red-500">{formErrors.price}</p>
                )}
              </div>

              {/* Количество */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Количество *
                </label>
                <input
                  type="number"
                  name="stock"
                  value={formData.stock}
                  onChange={handleInputChange}
                  className={`w-full px-3 py-2 border rounded-md ${formErrors.stock ? 'border-red-500' : 'border-gray-300'}`}
                />
                {formErrors.stock && (
                  <p className="mt-1 text-sm text-red-500">{formErrors.stock}</p>
                )}
              </div>

              {/* Категория */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Категория
                </label>
                <select
                  name="category_id"
                  value={formData.category_id || ''}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md"
                >
                  <option value="">Выберите категорию</option>
                  {categories.map(category => (
                    <option key={category.id} value={category.id}>
                      {category.name}
                    </option>
                  ))}
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
                  <option value="active">Активен</option>
                  <option value="inactive">Неактивен</option>
                </select>
              </div>

              {/* URL (slug) */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  URL (необязательно)
                </label>
                <input
                  type="text"
                  name="slug"
                  value={formData.slug || ''}
                  onChange={handleInputChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md"
                  placeholder="product-url"
                />
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

export default ShopProducts; 